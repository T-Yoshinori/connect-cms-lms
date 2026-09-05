<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('yuyu_learning_frames')) {
            Schema::create('yuyu_learning_frames', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('frame_id')->unique();
                $table->unsignedBigInteger('course_id')->nullable()->index();
                $table->timestamps();

                $table->foreign('course_id')
                    ->references('id')
                    ->on('yuyu_learning_courses')
                    ->onDelete('set null');
            });
        }

        $frames = DB::table('frames')
            ->where('plugin_name', 'yuyulearning')
            ->get();

        foreach ($frames as $frame) {
            if (!DB::table('yuyu_learning_frames')->where('frame_id', $frame->id)->exists()) {
                DB::table('yuyu_learning_frames')->insert([
                    'frame_id' => $frame->id,
                    'course_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (empty($frame->bucket_id)) {
                $bucket_id = DB::table('buckets')->insertGetId([
                    'bucket_name' => 'LMS',
                    'plugin_name' => 'yuyulearning',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('frames')
                    ->where('id', $frame->id)
                    ->update(['bucket_id' => $bucket_id]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('yuyu_learning_frames');
    }
};
