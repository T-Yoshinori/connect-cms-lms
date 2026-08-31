<?php

namespace App\Plugins\User\Lms\Services\Adapters;

use Illuminate\Support\Facades\DB;
use App\Models\User\Lms\LmsContent;

class LearningtaskContentAdapter implements ContentStatusAdapterInterface
{
    public function resolveStatus(LmsContent $content, int $user_id): ?string
    {
        if (empty($content->reference_id)) {
            return null;
        }

        $completed = DB::table('learningtasks_users_statuses')
            ->where('post_id', $content->reference_id)
            ->where('user_id', $user_id)
            ->where('task_status', 1)
            ->whereNull('deleted_at')
            ->exists();

        return $completed ? 'completed' : null;
    }
}

