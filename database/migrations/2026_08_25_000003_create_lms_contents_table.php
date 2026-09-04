<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yuyu_learning_contents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('section_id');
            $table->string('title');
            $table->string('content_type', 30);
            $table->string('reference_type', 30)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('reference_url')->nullable();
            $table->text('description')->nullable();
            $table->string('completion_type', 30);
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('section_id')->references('id')->on('yuyu_learning_sections')->onDelete('cascade');
            $table->index(['section_id', 'sort_order']);
            $table->index(['content_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yuyu_learning_contents');
    }
};

