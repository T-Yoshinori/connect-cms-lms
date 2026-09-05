<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yuyu_learning_content_progress', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('content_id');
            $table->string('status', 20)->default('not_started');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('completion_source', 30)->nullable();
            $table->timestamps();

            $table->foreign('enrollment_id')->references('id')->on('yuyu_learning_enrollments')->onDelete('cascade');
            $table->foreign('content_id')->references('id')->on('yuyu_learning_contents')->onDelete('cascade');
            $table->unique(['enrollment_id', 'content_id']);
            $table->index(['content_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yuyu_learning_content_progress');
    }
};
