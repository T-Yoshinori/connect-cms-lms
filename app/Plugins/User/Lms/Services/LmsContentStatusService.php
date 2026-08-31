<?php

namespace App\Plugins\User\Lms\Services;

use Illuminate\Support\Collection;

use App\Models\User\Lms\LmsContent;
use App\Models\User\Lms\LmsEnrollment;
use App\Plugins\User\Lms\Services\Adapters\ContentStatusAdapterInterface;
use App\Plugins\User\Lms\Services\Adapters\LearningtaskContentAdapter;
use App\Plugins\User\Lms\Services\Adapters\QuestionnaireContentAdapter;
use App\Plugins\User\Lms\Services\Adapters\QuizContentAdapter;

class LmsContentStatusService
{
    private $progressService;

    public function __construct(?LmsProgressService $progressService = null)
    {
        $this->progressService = $progressService ?: new LmsProgressService();
    }

    public function syncAutomaticCompletions(
        LmsEnrollment $enrollment,
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
        LmsEnrollment $enrollment,
        LmsContent $content,
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
        LmsEnrollment $enrollment,
        LmsContent $content,
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

    private function getAdapter(LmsContent $content): ?ContentStatusAdapterInterface
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

    private function getCompletionSource(LmsContent $content): string
    {
        return [
            'quiz' => 'quiz',
            'questionnaire' => 'questionnaire',
            'learningtask' => 'learningtask',
        ][$content->content_type] ?? 'system';
    }
}

