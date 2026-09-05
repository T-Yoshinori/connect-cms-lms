<?php

namespace App\Plugins\User\Yuyulearning\Services;

use Illuminate\Support\Collection;

use App\Models\User\YuyuLearning\YuyuLearningContent;
use App\Models\User\YuyuLearning\YuyuLearningEnrollment;
use App\Plugins\User\Yuyulearning\Services\Adapters\ContentStatusAdapterInterface;
use App\Plugins\User\Yuyulearning\Services\Adapters\LearningtaskContentAdapter;
use App\Plugins\User\Yuyulearning\Services\Adapters\QuestionnaireContentAdapter;
use App\Plugins\User\Yuyulearning\Services\Adapters\QuizContentAdapter;

class YuyuLearningContentStatusService
{
    private $progressService;

    public function __construct(?YuyuLearningProgressService $progressService = null)
    {
        $this->progressService = $progressService ?: new YuyuLearningProgressService();
    }

    public function syncAutomaticCompletions(
        YuyuLearningEnrollment $enrollment,
        Collection $contents,
        int $user_id
    ): void {
        foreach ($contents as $content) {
            $adapter = $this->getAdapter($content);
            if (!$adapter) {
                continue;
            }

            $this->applyResolvedStatus(
                $enrollment,
                $content,
                $adapter->resolveStatus($content, $user_id)
            );
        }
    }

    public function syncAutomaticCompletion(
        YuyuLearningEnrollment $enrollment,
        YuyuLearningContent $content,
        int $user_id
    ): bool {
        $adapter = $this->getAdapter($content);
        if (!$adapter) {
            return false;
        }

        $status = $adapter->resolveStatus($content, $user_id);
        $this->applyResolvedStatus($enrollment, $content, $status);

        return $status === 'completed';
    }

    private function applyResolvedStatus(
        YuyuLearningEnrollment $enrollment,
        YuyuLearningContent $content,
        ?string $status
    ): void {
        if ($status === 'completed') {
            $this->progressService->markCompleted(
                $enrollment,
                $content,
                $this->getCompletionSource($content)
            );
            return;
        }

        if ($status === 'failed') {
            $this->progressService->markFailed(
                $enrollment,
                $content,
                $this->getCompletionSource($content)
            );
            return;
        }

        if ($status === 'in_progress') {
            $this->progressService->markStarted($enrollment, $content);
        }
    }

    private function getAdapter(YuyuLearningContent $content): ?ContentStatusAdapterInterface
    {
        switch ($content->content_type) {
            case 'quiz':
                return new QuizContentAdapter();
            case 'questionnaire':
                return new QuestionnaireContentAdapter();
            case 'learningtask':
                return new LearningtaskContentAdapter();
            default:
                return null;
        }
    }

    private function getCompletionSource(YuyuLearningContent $content): string
    {
        return [
            'quiz' => 'quiz',
            'questionnaire' => 'questionnaire',
            'learningtask' => 'learningtask',
        ][$content->content_type] ?? 'system';
    }
}
