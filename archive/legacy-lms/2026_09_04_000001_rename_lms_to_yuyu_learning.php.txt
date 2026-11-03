<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename the pre-release LMS identifiers without losing demo-site data.
     *
     * New installations already create the yuyu_learning_* tables in the
     * earlier create migrations, so this migration only acts on upgrades.
     */
    public function up(): void
    {
        if (Schema::hasTable('lms_courses') && !Schema::hasTable('yuyu_learning_courses')) {
            $this->dropForeignKeys('lms');
            $this->renameTables([
                'lms_courses' => 'yuyu_learning_courses',
                'lms_sections' => 'yuyu_learning_sections',
                'lms_contents' => 'yuyu_learning_contents',
                'lms_enrollments' => 'yuyu_learning_enrollments',
                'lms_course_groups' => 'yuyu_learning_course_groups',
                'lms_content_progress' => 'yuyu_learning_content_progress',
                'lms_frames' => 'yuyu_learning_frames',
            ]);
            $this->addForeignKeys('yuyu_learning');
        }

        $this->renamePluginKey('lms', 'yuyulearning');
    }

    public function down(): void
    {
        $this->renamePluginKey('yuyulearning', 'lms');

        if (Schema::hasTable('yuyu_learning_courses') && !Schema::hasTable('lms_courses')) {
            $this->dropForeignKeys('yuyu_learning');
            $this->renameTables([
                'yuyu_learning_frames' => 'lms_frames',
                'yuyu_learning_content_progress' => 'lms_content_progress',
                'yuyu_learning_course_groups' => 'lms_course_groups',
                'yuyu_learning_enrollments' => 'lms_enrollments',
                'yuyu_learning_contents' => 'lms_contents',
                'yuyu_learning_sections' => 'lms_sections',
                'yuyu_learning_courses' => 'lms_courses',
            ]);
            $this->addForeignKeys('lms');
        }
    }

    private function renameTables(array $tables): void
    {
        foreach ($tables as $from => $to) {
            if (Schema::hasTable($from) && !Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }
    }

    private function dropForeignKeys(string $prefix): void
    {
        $foreignKeys = [
            "{$prefix}_sections" => ['course_id'],
            "{$prefix}_contents" => ['section_id'],
            "{$prefix}_enrollments" => ['course_id'],
            "{$prefix}_course_groups" => ['course_id'],
            "{$prefix}_content_progress" => ['enrollment_id', 'content_id'],
            "{$prefix}_frames" => ['course_id'],
        ];

        foreach ($foreignKeys as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->dropForeign([$column]);
                }
            });
        }
    }

    private function addForeignKeys(string $prefix): void
    {
        Schema::table("{$prefix}_sections", function (Blueprint $table) use ($prefix) {
            $table->foreign('course_id')->references('id')->on("{$prefix}_courses")->onDelete('cascade');
        });
        Schema::table("{$prefix}_contents", function (Blueprint $table) use ($prefix) {
            $table->foreign('section_id')->references('id')->on("{$prefix}_sections")->onDelete('cascade');
        });
        Schema::table("{$prefix}_enrollments", function (Blueprint $table) use ($prefix) {
            $table->foreign('course_id')->references('id')->on("{$prefix}_courses")->onDelete('cascade');
        });
        Schema::table("{$prefix}_course_groups", function (Blueprint $table) use ($prefix) {
            $table->foreign('course_id')->references('id')->on("{$prefix}_courses")->onDelete('cascade');
        });
        Schema::table("{$prefix}_content_progress", function (Blueprint $table) use ($prefix) {
            $table->foreign('enrollment_id')->references('id')->on("{$prefix}_enrollments")->onDelete('cascade');
            $table->foreign('content_id')->references('id')->on("{$prefix}_contents")->onDelete('cascade');
        });
        Schema::table("{$prefix}_frames", function (Blueprint $table) use ($prefix) {
            $table->foreign('course_id')->references('id')->on("{$prefix}_courses")->onDelete('set null');
        });
    }

    private function renamePluginKey(string $from, string $to): void
    {
        foreach (['frames', 'buckets'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'plugin_name')) {
                DB::table($table)
                    ->where('plugin_name', $from)
                    ->update(['plugin_name' => $to]);
            }
        }

        if (!Schema::hasTable('plugins') || !Schema::hasColumn('plugins', 'plugin_name')) {
            return;
        }

        $targetExists = DB::table('plugins')->where('plugin_name', $to)->exists();
        if (!$targetExists) {
            DB::table('plugins')
                ->where('plugin_name', $from)
                ->update(['plugin_name' => $to]);
        }

        if (Schema::hasColumn('plugins', 'plugin_name_full')) {
            DB::table('plugins')
                ->where('plugin_name', $to)
                ->update(['plugin_name_full' => $to === 'yuyulearning' ? 'YuyuLearning' : 'LMS']);
        }
    }
};
