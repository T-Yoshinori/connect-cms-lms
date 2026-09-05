<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yuyu_learning_frames', function (Blueprint $table) {
            $table->string('organizer_name', 191)
                ->nullable()
                ->after('course_id')
                ->comment('修了証に表示する主催者名');
        });
    }

    public function down(): void
    {
        Schema::table('yuyu_learning_frames', function (Blueprint $table) {
            $table->dropColumn('organizer_name');
        });
    }
};
