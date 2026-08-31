<?php

namespace App\Plugins\User\Lms\Services\Adapters;

use Illuminate\Support\Facades\DB;

use App\Models\User\Lms\LmsContent;
use App\Models\User\Quizzes\Quizzes;
use App\Models\User\Quizzes\QuizzesAttempts;

class QuizContentAdapter implements ContentStatusAdapterInterface
{
    /**
     * Quizzes側の受験結果と再受験設定からLMS教材状態を判定する。
     *
     * 小テスト教材は実際の起動先である frame_id を正本とし、
     * quiz_frames に現在設定されている quiz_id を使用する。
     * 既存の lms_contents.reference_id が不整合なら自動補正する。
     *
     * - 合格済み、または合格判定なしで採点済み => completed
     * - 不合格でも再受験可能 => in_progress
     * - 不合格かつ再受験不能 => failed
     * - 受験中、提出済み、採点待ち => in_progress
     * - 未受験 => null
     */
    public function resolveStatus(LmsContent $content, int $user_id): ?string
    {
        $quiz_id = $this->resolveQuizId($content);
        if (empty($quiz_id)) {
            return null;
        }

        $completed = QuizzesAttempts::query()
            ->where('quiz_id', $quiz_id)
            ->where('user_id', $user_id)
            ->where('is_preview', false)
            ->where(function ($query) {
                $query->where('pass_status', 'passed')
                    ->orWhere(function ($none_query) {
                        $none_query->where('status', 'graded')
                            ->where('passing_type_snapshot', Quizzes::PASSING_TYPE_NONE)
                            ->where('pass_status', 'not_applicable');
                    });
            })
            ->exists();

        if ($completed) {
            return 'completed';
        }

        $latest_attempt = QuizzesAttempts::query()
            ->where('quiz_id', $quiz_id)
            ->where('user_id', $user_id)
            ->where('is_preview', false)
            ->orderByDesc('attempt_no')
            ->orderByDesc('id')
            ->first();

        if (!$latest_attempt) {
            return null;
        }

        if (
            $latest_attempt->status !== 'graded'
            || $latest_attempt->pass_status === 'pending'
        ) {
            return 'in_progress';
        }

        if ($latest_attempt->pass_status !== 'failed') {
            return 'in_progress';
        }

        $quiz = Quizzes::find($quiz_id);
        if (!$quiz) {
            return 'in_progress';
        }

        if ($quiz->retry_type === Quizzes::RETRY_TYPE_UNLIMITED) {
            return 'in_progress';
        }

        $finished_attempt_count = QuizzesAttempts::query()
            ->where('quiz_id', $quiz_id)
            ->where('user_id', $user_id)
            ->where('is_preview', false)
            ->whereIn('status', ['submitted', 'graded', 'expired'])
            ->count();

        if ($quiz->retry_type === Quizzes::RETRY_TYPE_ONCE) {
            return 'failed';
        }

        if (
            $quiz->retry_type === Quizzes::RETRY_TYPE_LIMITED
            && $finished_attempt_count >= (int) $quiz->retry_limit
        ) {
            return 'failed';
        }

        return 'in_progress';
    }

    private function resolveQuizId(LmsContent $content): ?int
    {
        if (!empty($content->frame_id)) {
            $quiz_id = DB::table('quiz_frames')
                ->where('frame_id', (int) $content->frame_id)
                ->value('quiz_id');

            if (!empty($quiz_id)) {
                $quiz_id = (int) $quiz_id;

                if ((int) $content->reference_id !== $quiz_id) {
                    $content->reference_id = $quiz_id;
                    $content->save();
                }

                return $quiz_id;
            }
        }

        return empty($content->reference_id) ? null : (int) $content->reference_id;
    }
}

