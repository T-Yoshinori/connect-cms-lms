<?php

namespace App\Plugins\User\Yuyulearning\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\User\YuyuLearning\YuyuLearningCourse;

class YuyuLearningDemoResetService
{
    public function preview(int $course_id, array $user_ids): array
    {
        $targets = $this->resolveTargets($course_id, $user_ids);

        return [
            'course' => $targets['course'],
            'user_ids' => $targets['user_ids'],
            'enrollments' => DB::table('yuyu_learning_enrollments')->whereIn('id', $targets['enrollment_ids'])->count(),
            'progresses' => DB::table('yuyu_learning_content_progress')->whereIn('enrollment_id', $targets['enrollment_ids'])->count(),
            'questionnaire_inputs' => DB::table('forms_inputs')->whereIn('id', $targets['forms_input_ids'])->count(),
            'quiz_attempts' => DB::table('yuyu_quiz_attempts')->whereIn('id', $targets['quiz_attempt_ids'])->count(),
            'learningtask_statuses' => DB::table('learningtasks_users_statuses')->whereIn('id', $targets['learningtask_status_ids'])->count(),
            'learningtask_uploads' => DB::table('uploads')->whereIn('id', $targets['learningtask_upload_ids'])->count(),
        ];
    }

    public function reset(int $course_id, array $user_ids): array
    {
        $targets = $this->resolveTargets($course_id, $user_ids);
        $result = $this->preview($course_id, $user_ids);
        $uploads = DB::table('uploads')
            ->whereIn('id', $targets['learningtask_upload_ids'])
            ->where('plugin_name', 'learningtasks')
            ->get(['id', 'extension']);

        DB::transaction(function () use ($targets) {
            DB::table('forms_input_cols')->whereIn('forms_inputs_id', $targets['forms_input_ids'])->delete();
            DB::table('forms_inputs')->whereIn('id', $targets['forms_input_ids'])->delete();

            // yuyu_quiz_attempts 以下は外部キーのcascadeで解答・採点結果も削除される。
            DB::table('yuyu_quiz_attempts')->whereIn('id', $targets['quiz_attempt_ids'])->delete();

            DB::table('learningtasks_users_statuses')->whereIn('id', $targets['learningtask_status_ids'])->delete();
            DB::table('uploads')
                ->whereIn('id', $targets['learningtask_upload_ids'])
                ->where('plugin_name', 'learningtasks')
                ->delete();

            DB::table('yuyu_learning_content_progress')->whereIn('enrollment_id', $targets['enrollment_ids'])->delete();
            DB::table('yuyu_learning_enrollments')->whereIn('id', $targets['enrollment_ids'])->delete();
        });

        $failed_files = [];
        foreach ($uploads as $upload) {
            $path = $this->getUploadDirectory((int) $upload->id) . '/' . $upload->id . '.' . $upload->extension;
            if (Storage::exists($path) && !Storage::delete($path)) {
                $failed_files[] = $path;
            }
        }

        $result['failed_files'] = $failed_files;

        return $result;
    }

    private function resolveTargets(int $course_id, array $user_ids): array
    {
        $user_ids = collect($user_ids)->map(function ($user_id) {
            return (int) $user_id;
        })->filter()->unique()->values();

        if ($user_ids->isEmpty()) {
            throw new \InvalidArgumentException('デモ受講者IDが指定されていません。');
        }

        $course = YuyuLearningCourse::find($course_id);
        if (!$course) {
            throw new \InvalidArgumentException('指定されたYuyuLearningコースが見つかりません。');
        }

        $contents = DB::table('yuyu_learning_contents')
            ->join('yuyu_learning_sections', 'yuyu_learning_sections.id', '=', 'yuyu_learning_contents.section_id')
            ->where('yuyu_learning_sections.course_id', $course_id)
            ->get([
                'yuyu_learning_contents.content_type',
                'yuyu_learning_contents.reference_id',
                'yuyu_learning_contents.frame_id',
                'yuyu_learning_contents.created_at',
            ]);

        $enrollment_ids = DB::table('yuyu_learning_enrollments')
            ->where('course_id', $course_id)
            ->whereIn('user_id', $user_ids)
            ->pluck('id');

        $forms_input_ids = collect();
        foreach ($contents->where('content_type', 'questionnaire') as $content) {
            if (empty($content->reference_id)) {
                continue;
            }
            $forms_input_ids = $forms_input_ids->merge(DB::table('forms_inputs')
                ->where('forms_id', (int) $content->reference_id)
                ->whereIn('created_id', $user_ids)
                ->where('created_at', '>=', $content->created_at)
                ->pluck('id'));
        }

        $quiz_ids = $contents->where('content_type', 'quiz')->map(function ($content) {
            if (!empty($content->frame_id)) {
                $quiz_id = DB::table('yuyu_quiz_frames')->where('frame_id', (int) $content->frame_id)->value('quiz_id');
                if (!empty($quiz_id)) {
                    return (int) $quiz_id;
                }
            }
            return empty($content->reference_id) ? null : (int) $content->reference_id;
        })->filter()->unique()->values();

        $quiz_attempt_ids = DB::table('yuyu_quiz_attempts')
            ->whereIn('quiz_id', $quiz_ids)
            ->whereIn('user_id', $user_ids)
            ->where('is_preview', false)
            ->pluck('id');

        $learningtask_post_ids = $contents->where('content_type', 'learningtask')
            ->pluck('reference_id')->filter()->map(function ($post_id) {
                return (int) $post_id;
            })->unique()->values();
        $learningtask_statuses = DB::table('learningtasks_users_statuses')
            ->whereIn('post_id', $learningtask_post_ids)
            ->whereIn('user_id', $user_ids)
            ->get(['id', 'upload_id']);
        $learningtask_status_ids = $learningtask_statuses->pluck('id');
        $learningtask_upload_ids = $learningtask_statuses->pluck('upload_id')->filter()->unique()->values();

        // 万一ほかの履歴も同じuploadを参照している場合、そのuploadは削除対象から外す。
        if ($learningtask_upload_ids->isNotEmpty()) {
            $shared_upload_ids = DB::table('learningtasks_users_statuses')
                ->whereIn('upload_id', $learningtask_upload_ids)
                ->whereNotIn('id', $learningtask_status_ids)
                ->pluck('upload_id');
            $learningtask_upload_ids = $learningtask_upload_ids->diff($shared_upload_ids)->values();
        }

        return [
            'course' => $course,
            'user_ids' => $user_ids,
            'enrollment_ids' => $enrollment_ids,
            'forms_input_ids' => $forms_input_ids->unique()->values(),
            'quiz_attempt_ids' => $quiz_attempt_ids,
            'learningtask_status_ids' => $learningtask_status_ids,
            'learningtask_upload_ids' => $learningtask_upload_ids,
        ];
    }

    private function getUploadDirectory(int $upload_id): string
    {
        if ($upload_id <= 0) {
            return config('connect.directory_base') . '0';
        }

        $quotient = floor($upload_id / config('connect.directory_file_limit'));
        $remainder = $upload_id % config('connect.directory_file_limit');
        $sub_directory = $remainder === 0 ? $quotient : $quotient + 1;

        return config('connect.directory_base') . $sub_directory;
    }
}
