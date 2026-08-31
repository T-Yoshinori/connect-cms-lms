<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_enrollments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('user_id');
            $table->string('enrollment_source', 20)->default('individual');
            $table->unsignedBigInteger('source_group_id')->nullable();
            $table->string('status', 20)->default('not_started');
            $table->dateTime('enrolled_at');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('lms_courses')->onDelete('cascade');
            $table->unique(['course_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index('source_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_enrollments');
    }
};

