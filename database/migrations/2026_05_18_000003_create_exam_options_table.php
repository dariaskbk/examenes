<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->text('option_text');
            $table->string('image')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('exam_questions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_options');
    }
};
