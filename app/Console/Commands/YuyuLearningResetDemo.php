<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Plugins\User\Yuyulearning\Services\YuyuLearningDemoResetService;

class YuyuLearningResetDemo extends Command
{
    protected $signature = 'yuyulearning:reset-demo
                            {--dry-run : 削除せず対象件数だけ確認する}
                            {--force : 確認を省略して実行する}';

    protected $description = '指定したデモコース・デモ受講者の受講結果を初期状態に戻します';

    public function handle(YuyuLearningDemoResetService $service): int
    {
        if (!filter_var(config('yuyulearning.demo_reset.enabled'), FILTER_VALIDATE_BOOLEAN)) {
            $this->error('YUYU_LEARNING_DEMO_RESET_ENABLED=true のときだけ実行できます。');
            return self::FAILURE;
        }

        $course_id = (int) config('yuyulearning.demo_reset.course_id');
        $user_ids = $this->parseUserIds((string) config('yuyulearning.demo_reset.user_ids'));

        if ($course_id <= 0 || empty($user_ids)) {
            $this->error('YUYU_LEARNING_DEMO_COURSE_ID と YUYU_LEARNING_DEMO_USER_IDS を設定してください。');
            return self::FAILURE;
        }

        try {
            $preview = $service->preview($course_id, $user_ids);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('YuyuLearning デモデータ初期化');
        $this->line('コース: ' . $preview['course']->name . ' (ID: ' . $preview['course']->id . ')');
        $this->line('受講者ID: ' . implode(', ', $preview['user_ids']->all()));
        $this->table(['対象', '件数'], $this->rows($preview));

        if ($this->option('dry-run')) {
            $this->comment('確認のみのため、データは削除していません。');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('上記の受講結果を初期化しますか？')) {
            $this->comment('初期化を中止しました。');
            return self::SUCCESS;
        }

        try {
            $result = $service->reset($course_id, $user_ids);
        } catch (\Throwable $e) {
            $this->error('初期化に失敗しました: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('デモ受講データを初期状態に戻しました。');
        $this->table(['対象', '削除件数'], $this->rows($result));

        if (!empty($result['failed_files'])) {
            $this->warn('次の提出ファイルを削除できませんでした。');
            foreach ($result['failed_files'] as $path) {
                $this->line($path);
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function parseUserIds(string $value): array
    {
        return collect(explode(',', $value))
            ->map(function ($user_id) {
                return (int) trim($user_id);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function rows(array $result): array
    {
        return [
            ['YuyuLearning受講記録', $result['enrollments']],
            ['YuyuLearning教材進捗', $result['progresses']],
            ['アンケート回答', $result['questionnaire_inputs']],
            ['小テスト受験', $result['quiz_attempts']],
            ['課題提出・評価履歴', $result['learningtask_statuses']],
            ['課題提出ファイル', $result['learningtask_uploads']],
        ];
    }
}
