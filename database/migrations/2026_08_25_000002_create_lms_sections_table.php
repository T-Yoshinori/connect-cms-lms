<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yuyu_learning_sections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('yuyu_learning_courses')->onDelete('cascade');
            $table->index(['course_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yuyu_learning_sections');
    }
};

