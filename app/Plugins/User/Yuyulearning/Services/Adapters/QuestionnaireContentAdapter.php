<?php

namespace App\Plugins\User\Yuyulearning\Services\Adapters;

use Illuminate\Support\Facades\DB;
use App\Enums\FormStatusType;
use App\Models\User\YuyuLearning\YuyuLearningContent;

class QuestionnaireContentAdapter implements ContentStatusAdapterInterface
{
    public function resolveStatus(YuyuLearningContent $content, int $user_id): ?string
    {
        if (empty($content->reference_id)) {
            return null;
        }

        $completed = DB::table('forms_inputs')
            ->where('forms_id', $content->reference_id)
            ->where('created_id', $user_id)
            ->where('status', FormStatusType::active)
            // 教材登録前の回答（Forms作成・確認時のデータなど）は完了判定に使用しない。
            ->where('created_at', '>=', $content->created_at)
            ->exists();

        return $completed ? 'completed' : null;
    }
}
