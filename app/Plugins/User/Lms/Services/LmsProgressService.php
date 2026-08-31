<?php

namespace App\Plugins\User\Lms\Services;

use Illuminate\Support\Facades\DB;

use App\Models\User\Lms\LmsContent;
use App\Models\User\Lms\LmsContentProgress;
use App\Models\User\Lms\LmsEnrollment;

class LmsProgressService
{
    private const SELF_COMPLETION_TYPES = [
        'general',
        'blog',
        'custom',
    ];

    /**
     * 第1版では、そのLMSページを閲覧できるログインユーザーを受講者として扱う。
     *
     * ページ／フレームの閲覧可否はConnect-CMS標準の権限機構へ委ね、
     * LMS側ではコースごとの受講グループを重ねて判定しない。
     * lms_enrollments は個人別受講実績として内部生成する。
     */
    public function resolveEnrollmentForCourse(int $course_id, int $user_id): LmsEnrollment
    {
        return DB::transaction(function () use ($course_id, $user_id) {
            $enrollment = LmsEnrollment::query()
                ->where('course_id', $course_id)
                ->where('user_id', $user_id)
                ->lockForUpdate()
                ->first();

            if (!$enrollment) {
                $enrollment = new LmsEnrollment();
                $enrollment->course_id = $course_id;
                $enrollment->user_id = $user_id;
                $enrollment->enrollment_source = 'page';
                $enrollment->source_group_id = null;
                $enrollment->status = 'not_started';
                $enrollment->enrolled_at = now();
                $enrollment->save();
            }

            return $enrollment->fresh();
        });
    }

    public function getEnrollmentForContent(LmsContent $content, int $user_id): ?LmsEnrollment
    {
        $content->loadMissing('section');

        if (empty($content->section) || empty($content->section->course_id)) {
            return null;
        }

        return $this->resolveEnrollmentForCourse((int) $content->section->course_id, $user_id);
    }

    public function getProgressForUser(LmsContent $content, int $user_id): ?LmsContentProgress
    {
        $enrollment = $this->getEnrollmentForContent($content, $user_id);
        if (!$enrollment) {
            return null;
        }

        return LmsContentProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('content_id', $content->id)
            ->first();
    }

    public function markStartedForUser(LmsContent $content, int $user_id): ?LmsContentProgress
    {
        $enrollment = $this->getEnrollmentForContent($content, $user_id);
        if (!$enrollment) {
            return null;
        }

        return $this->markStarted($enrollment, $content);
    }

    public function markSelfCompletedForUser(LmsContent $content, int $user_id): ?LmsContentProgress
    {
        if (!in_array($content->content_type, self::SELF_COMPLETION_TYPES, true)) {
            return null;
        }

        $enrollment = $this->getEnrollmentForContent($content, $user_id);
        if (!$enrollment) {
            return null;
        }

        return $this->markCompleted($enrollment, $content, 'self');
    }

    public function markStarted(LmsEnrollment $enrollment, LmsContent $content): LmsContentProgress
    {
        return DB::transaction(function () use ($enrollment, $content) {
            $progress = LmsContentProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('content_id', $content->id)
                ->lockForUpdate()
                ->first();

            if (!$progress) {
                $progress = new LmsContentProgress();
                $progress->enrollment_id = $enrollment->id;
                $progress->content_id = $content->id;
                $progress->status = 'in_progress';
                $progress->started_at = now();
                $progress->save();
            } elseif (in_array($progress->status, ['not_started', 'failed'], true)) {
                $progress->status = 'in_progress';
                $progress->started_at = $progress->started_at ?: now();
                $progress->completed_at = null;
                $progress->completion_source = null;
                $progress->save();
            }

            if ($enrollment->status === 'not_started') {
                $enrollment->status = 'in_progress';
                $enrollment->started_at = $enrollment->started_at ?: now();
                $enrollment->save();
            }

            return $progress->fresh();
        });
    }

    public function markCompleted(
        LmsEnrollment $enrollment,
        LmsContent $content,
        string $completion_source
    ): LmsContentProgress {
        $progress = DB::transaction(function () use ($enrollment, $content, $completion_source) {
            $progress = LmsContentProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('content_id', $content->id)
                ->lockForUpdate()
                ->first();

            if (!$progress) {
                $progress = new LmsContentProgress();
                $progress->enrollment_id = $enrollment->id;
                $progress->content_id = $content->id;
            }

            $progress->status = 'completed';
            $progress->started_at = $progress->started_at ?: now();
            $progress->completed_at = $progress->completed_at ?: now();
            $progress->completion_source = $completion_source;
            $progress->save();

            if ($enrollment->status === 'not_started') {
                $enrollment->status = 'in_progress';
                $enrollment->started_at = $enrollment->started_at ?: now();
                $enrollment->save();
            }

            return $progress->fresh();
        });

        $this->syncCourseCompletion($enrollment);

        return $progress;
    }

    public function markFailed(
        LmsEnrollment $enrollment,
        LmsContent $content,
        string $completion_source
    ): LmsContentProgress {
        return DB::transaction(function () use ($enrollment, $content, $completion_source) {
            $progress = LmsContentProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('content_id', $content->id)
                ->lockForUpdate()
                ->first();

            if (!$progress) {
                $progress = new LmsContentProgress();
                $progress->enrollment_id = $enrollment->id;
                $progress->content_id = $content->id;
            }

            // 一度完了した教材は、後続の同期で不合格へ戻さない。
            if ($progress->status !== 'completed') {
                $progress->status = 'failed';
                $progress->started_at = $progress->started_at ?: now();
                $progress->completed_at = null;
                $progress->completion_source = $completion_source;
                $progress->save();
            }

            if ($enrollment->status === 'not_started') {
                $enrollment->status = 'in_progress';
                $enrollment->started_at = $enrollment->started_at ?: now();
                $enrollment->save();
            }

            return $progress->fresh();
        });
    }

    public function syncCourseCompletion(LmsEnrollment $enrollment): bool
    {
        $required_content_ids = DB::table('lms_contents')
            ->join('lms_sections', 'lms_sections.id', '=', 'lms_contents.section_id')
            ->where('lms_sections.course_id', $enrollment->course_id)
            ->where('lms_contents.is_required', true)
            ->pluck('lms_contents.id');

        if ($required_content_ids->isEmpty()) {
            return false;
        }

        $completed_count = LmsContentProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('content_id', $required_content_ids)
            ->where('status', 'completed')
            ->count();

        if ($completed_count !== $required_content_ids->count()) {
            return false;
        }

        if ($enrollment->status !== 'completed') {
            $enrollment->status = 'completed';
            $enrollment->started_at = $enrollment->started_at ?: now();
            $enrollment->completed_at = $enrollment->completed_at ?: now();
            $enrollment->save();
        }

        return true;
    }
}

