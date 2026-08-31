<?php

namespace App\Plugins\User\Lms\Services;

use Illuminate\Support\Facades\DB;

use App\Enums\UserStatus;
use App\Models\Common\Page;
use App\Models\User\Lms\LmsCourse;
use App\Models\User\Lms\LmsEnrollment;
use App\User;

class LmsAdminProgressService
{
    public function build(LmsCourse $course, int $page_id): array
    {
        $course->load([
            'sections' => function ($query) {
                $query->with([
                    'contents' => function ($contents) {
                        $contents->orderBy('sort_order')->orderBy('id');
                    },
                ])->orderBy('sort_order')->orderBy('id');
            },
        ]);

        $contents = $course->sections
            ->flatMap(function ($section) {
                return $section->contents->map(function ($content) use ($section) {
                    $content->section_title = $section->title;
                    return $content;
                });
            })
            ->values();

        $required_content_ids = $contents
            ->where('is_required', true)
            ->pluck('id');

        $existing_enrollments = LmsEnrollment::query()
            ->where('course_id', $course->id)
            ->get();

        $status_service = new LmsContentStatusService();
        foreach ($existing_enrollments as $enrollment) {
            $status_service->syncAutomaticCompletions(
                $enrollment,
                $contents,
                (int) $enrollment->user_id
            );
        }

        $existing_enrollments = LmsEnrollment::query()
            ->where('course_id', $course->id)
            ->with(['user', 'progresses'])
            ->get()
            ->keyBy('user_id');

        $eligible_users = $this->getEligibleUsers($page_id)->keyBy('id');

        $existing_users = $existing_enrollments
            ->pluck('user')
            ->filter()
            ->keyBy('id');

        $users = $eligible_users->union($existing_users);

        $participant_user_ids = $eligible_users->keys()
            ->merge($existing_enrollments->keys())
            ->map(function ($user_id) {
                return (int) $user_id;
            })
            ->unique()
            ->values();

        $participants = $participant_user_ids
            ->map(function ($user_id) use ($course, $users, $existing_enrollments) {
                $enrollment = $existing_enrollments->get($user_id);
                $user = $users->get($user_id);

                if (!$enrollment) {
                    $enrollment = new LmsEnrollment([
                        'course_id' => $course->id,
                        'user_id' => $user_id,
                        'status' => 'not_started',
                    ]);
                    $enrollment->setRelation('user', $user);
                    $enrollment->setRelation('progresses', collect());
                }

                return $enrollment;
            })
            ->sortBy(function ($enrollment) {
                return sprintf(
                    '%s:%010d',
                    optional($enrollment->user)->name ?: '',
                    $enrollment->user_id
                );
            })
            ->values();

        $quiz_contents = $contents
            ->where('content_type', 'quiz')
            ->filter(function ($content) {
                return !empty($content->reference_id);
            });

        $quiz_results = $this->getQuizResults(
            $quiz_contents->pluck('reference_id')->unique()->values(),
            $participant_user_ids
        );

        $rows = $participants->map(function ($enrollment) use (
            $contents,
            $required_content_ids,
            $quiz_results
        ) {
            $progresses = $enrollment->progresses->keyBy('content_id');
            $required_total = $required_content_ids->count();
            $required_completed = $progresses
                ->whereIn('content_id', $required_content_ids)
                ->where('status', 'completed')
                ->count();

            $content_progresses = [];
            foreach ($contents as $content) {
                $progress = $progresses->get($content->id);
                $quiz_result = null;

                if ($content->content_type === 'quiz' && !empty($content->reference_id)) {
                    $quiz_result = $quiz_results->get(
                        $this->quizResultKey($enrollment->user_id, $content->reference_id)
                    );
                }

                $content_progresses[$content->id] = [
                    'status' => $progress ? $progress->status : 'not_started',
                    'started_at' => $progress ? $progress->started_at : null,
                    'completed_at' => $progress ? $progress->completed_at : null,
                    'quiz_result' => $quiz_result,
                ];
            }

            return [
                'enrollment' => $enrollment,
                'user' => $enrollment->user,
                'course_status' => $enrollment->status ?: 'not_started',
                'required_total' => $required_total,
                'required_completed' => $required_completed,
                'percentage' => $required_total > 0
                    ? (int) round(($required_completed / $required_total) * 100)
                    : 0,
                'content_progresses' => $content_progresses,
            ];
        });

        return [
            'contents' => $contents,
            'rows' => $rows,
            'summary' => [
                'total' => $rows->count(),
                'not_started' => $rows->where('course_status', 'not_started')->count(),
                'in_progress' => $rows->where('course_status', 'in_progress')->count(),
                'completed' => $rows->where('course_status', 'completed')->count(),
            ],
        ];
    }

    private function getEligibleUsers(int $page_id)
    {
        $page = Page::find($page_id);
        if (!$page) {
            return collect();
        }

        $page_tree = $page->getPageTreeByGoingBackParent(null);
        $membership_page = $page_tree->first(function ($page_item) {
            return in_array((int) $page_item->membership_flag, [1, 2], true);
        });

        if ($membership_page && (int) $membership_page->membership_flag === 1) {
            return User::query()
                ->join('group_users', function ($join) {
                    $join->on('group_users.user_id', '=', 'users.id')
                        ->whereNull('group_users.deleted_at');
                })
                ->join('groups', function ($join) {
                    $join->on('groups.id', '=', 'group_users.group_id')
                        ->whereNull('groups.deleted_at');
                })
                ->join('page_roles', function ($join) use ($membership_page) {
                    $join->on('page_roles.group_id', '=', 'group_users.group_id')
                        ->where('page_roles.page_id', '=', $membership_page->id)
                        ->where('page_roles.role_value', '=', 1)
                        ->whereNull('page_roles.deleted_at');
                })
                ->where('users.status', UserStatus::active)
                ->select('users.*')
                ->distinct()
                ->get();
        }

        return User::query()
            ->where('status', UserStatus::active)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    private function getQuizResults($quiz_ids, $user_ids)
    {
        if ($quiz_ids->isEmpty() || $user_ids->isEmpty()) {
            return collect();
        }

        return DB::table('quiz_attempts')
            ->whereIn('quiz_id', $quiz_ids)
            ->whereIn('user_id', $user_ids)
            ->where('is_preview', false)
            ->where('status', 'graded')
            ->orderByDesc('score_rate')
            ->orderByDesc('total_score')
            ->orderByDesc('attempt_no')
            ->orderByDesc('id')
            ->get()
            ->groupBy(function ($attempt) {
                return $this->quizResultKey($attempt->user_id, $attempt->quiz_id);
            })
            ->map(function ($attempts) {
                return $attempts->first();
            });
    }

    private function quizResultKey($user_id, $quiz_id): string
    {
        return (int) $user_id . ':' . (int) $quiz_id;
    }
}

