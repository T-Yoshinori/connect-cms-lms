<?php

namespace App\Plugins\User\Lms\Services\Adapters;

use App\Models\User\Lms\LmsContent;

interface ContentStatusAdapterInterface
{
    /**
     * 教材実体の状態からLMSへ反映すべき進捗状態を返す。
     *
     * completed / failed / in_progress を返す。
     * LMS側で状態を確定できない場合は null を返す。
     */
    public function resolveStatus(LmsContent $content, int $user_id): ?string;
}

