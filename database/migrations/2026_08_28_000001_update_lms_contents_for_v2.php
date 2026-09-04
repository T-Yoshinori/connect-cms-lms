<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $add_page_frame_index = false;

        if (!Schema::hasColumn('yuyu_learning_contents', 'plugin_name')) {
            Schema::table('yuyu_learning_contents', function (Blueprint $table) {
                $table->string('plugin_name', 50)->nullable()->after('content_type');
            });
        }

        if (!Schema::hasColumn('yuyu_learning_contents', 'action')) {
            Schema::table('yuyu_learning_contents', function (Blueprint $table) {
                $table->string('action', 50)->nullable()->after('plugin_name');
            });
        }

        if (!Schema::hasColumn('yuyu_learning_contents', 'page_id')) {
            Schema::table('yuyu_learning_contents', function (Blueprint $table) {
                $table->unsignedBigInteger('page_id')->nullable()->after('reference_id');
            });
            $add_page_frame_index = true;
        }

        if (!Schema::hasColumn('yuyu_learning_contents', 'frame_id')) {
            Schema::table('yuyu_learning_contents', function (Blueprint $table) {
                $table->unsignedBigInteger('frame_id')->nullable()->after('page_id');
            });
            $add_page_frame_index = true;
        }

        // 新規追加時は複合INDEXも作成する。
        // 途中失敗からの再実行時は、最初のALTERで既にINDEXまで作成済みの可能性があるため再作成しない。
        if ($add_page_frame_index) {
            Schema::table('yuyu_learning_contents', function (Blueprint $table) {
                $table->index(['page_id', 'frame_id']);
            });
        }

        if (Schema::hasColumn('yuyu_learning_contents', 'reference_type')) {
            Schema::table('yuyu_learning_contents', function (Blueprint $table) {
                $table->dropColumn('reference_type');
            });
        }

        if (Schema::hasColumn('yuyu_learning_contents', 'completion_type')) {
            Schema::table('yuyu_learning_contents', function (Blueprint $table) {
                $table->dropColumn('completion_type');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('yuyu_learning_contents', 'reference_type')) {
            Schema::table('yuyu_learning_contents', function (Blueprint $table) {
                $table->string('reference_type', 30)->nullable()->after('content_type');
            });
        }

        if (!Schema::hasColumn('yuyu_learning_contents', 'completion_type')) {
            Schema::table('yuyu_learning_contents', function (Blueprint $table) {
                $table->string('completion_type', 30)->after('description');
            });
        }

        if (Schema::hasColumn('yuyu_learning_contents', 'page_id') && Schema::hasColumn('yuyu_learning_contents', 'frame_id')) {
            try {
                Schema::table('yuyu_learning_contents', function (Blueprint $table) {
                    $table->dropIndex(['page_id', 'frame_id']);
                });
            } catch (\Throwable $e) {
                // INDEXが存在しない途中状態でもrollbackを継続する。
            }
        }

        foreach (['plugin_name', 'action', 'page_id', 'frame_id'] as $column) {
            if (Schema::hasColumn('yuyu_learning_contents', $column)) {
                Schema::table('yuyu_learning_contents', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

