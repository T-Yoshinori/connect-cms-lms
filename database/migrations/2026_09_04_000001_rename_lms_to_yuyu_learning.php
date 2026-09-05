<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'lms_courses' => 'yuyu_learning_courses',
        'lms_sections' => 'yuyu_learning_sections',
        'lms_contents' => 'yuyu_learning_contents',
        'lms_enrollments' => 'yuyu_learning_enrollments',
        'lms_course_groups' => 'yuyu_learning_course_groups',
        'lms_content_progress' => 'yuyu_learning_content_progress',
        'lms_frames' => 'yuyu_learning_frames',
    ];

    private const FOREIGN_KEYS = [
        'sections' => [
            ['column' => 'course_id', 'references' => 'id', 'on' => 'courses', 'delete' => 'cascade'],
        ],
        'contents' => [
            ['column' => 'section_id', 'references' => 'id', 'on' => 'sections', 'delete' => 'cascade'],
        ],
        'enrollments' => [
            ['column' => 'course_id', 'references' => 'id', 'on' => 'courses', 'delete' => 'cascade'],
        ],
        'course_groups' => [
            ['column' => 'course_id', 'references' => 'id', 'on' => 'courses', 'delete' => 'cascade'],
        ],
        'content_progress' => [
            ['column' => 'enrollment_id', 'references' => 'id', 'on' => 'enrollments', 'delete' => 'cascade'],
            ['column' => 'content_id', 'references' => 'id', 'on' => 'contents', 'delete' => 'cascade'],
        ],
        'frames' => [
            ['column' => 'course_id', 'references' => 'id', 'on' => 'courses', 'delete' => 'set null'],
        ],
    ];

    public function up(): void
    {
        if ($this->hasLegacyTables()) {
            $this->dropForeignKeys('lms_');

            foreach (self::TABLES as $old => $new) {
                if (Schema::hasTable($old) && !Schema::hasTable($new)) {
                    Schema::rename($old, $new);
                }
            }

            $this->addForeignKeys('yuyu_learning_');
        }

        $this->renamePluginKey('lms', 'yuyulearning');
    }

    public function down(): void
    {
        $this->renamePluginKey('yuyulearning', 'lms');

        if ($this->hasYuyuLearningTables()) {
            $this->dropForeignKeys('yuyu_learning_');

            foreach (array_reverse(self::TABLES, true) as $old => $new) {
                if (Schema::hasTable($new) && !Schema::hasTable($old)) {
                    Schema::rename($new, $old);
                }
            }

            $this->addForeignKeys('lms_');
        }
    }

    private function hasLegacyTables(): bool
    {
        return Schema::hasTable('lms_courses') && !Schema::hasTable('yuyu_learning_courses');
    }

    private function hasYuyuLearningTables(): bool
    {
        return Schema::hasTable('yuyu_learning_courses') && !Schema::hasTable('lms_courses');
    }

    private function renamePluginKey(string $from, string $to): void
    {
        foreach (['frames', 'buckets', 'plugins'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'plugin_name')) {
                DB::table($table)->where('plugin_name', $from)->update(['plugin_name' => $to]);
            }
        }
    }

    private function dropForeignKeys(string $prefix): void
    {
        foreach (self::FOREIGN_KEYS as $suffix => $keys) {
            $tableName = $prefix . $suffix;
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($keys) {
                foreach ($keys as $key) {
                    $table->dropForeign([$key['column']]);
                }
            });
        }
    }

    private function addForeignKeys(string $prefix): void
    {
        foreach (self::FOREIGN_KEYS as $suffix => $keys) {
            $tableName = $prefix . $suffix;
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($keys, $prefix) {
                foreach ($keys as $key) {
                    $table->foreign($key['column'])
                        ->references($key['references'])
                        ->on($prefix . $key['on'])
                        ->onDelete($key['delete']);
                }
            });
        }
    }
};
