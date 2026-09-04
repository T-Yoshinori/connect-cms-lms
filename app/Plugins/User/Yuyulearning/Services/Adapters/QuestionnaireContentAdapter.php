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
            ->exists();

        return $completed ? 'completed' : null;
    }
}

