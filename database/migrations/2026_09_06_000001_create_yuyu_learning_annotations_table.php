<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yuyu_learning_annotations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('content_id');
            $table->unsignedBigInteger('blog_post_id')->nullable();
            $table->string('annotation_type', 20)->comment('highlight / note');
            $table->string('color', 20)->nullable()->comment('yellow / green / blue / pink');
            $table->text('selected_text');
            $table->text('prefix_text')->nullable();
            $table->text('suffix_text')->nullable();
            $table->unsignedInteger('start_offset');
            $table->unsignedInteger('end_offset');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'content_id'], 'yuyu_learning_annotations_user_content_index');
            $table->index(['content_id', 'blog_post_id'], 'yuyu_learning_annotations_content_post_index');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('content_id')->references('id')->on('yuyu_learning_contents')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yuyu_learning_annotations');
    }
};
