<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable(); // references sicore.subjects
            $table->unsignedBigInteger('year_id')->nullable();    // references sicore.years
            $table->unsignedBigInteger('user_id');                // references sicore.users (teacher)
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->dateTime('available_from')->nullable();
            $table->dateTime('available_until')->nullable();
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_answers')->default(false);
            $table->unsignedInteger('max_attempts')->default(1);
            $table->boolean('show_results')->default(true);
            $table->boolean('show_correct_answers')->default(false);
            $table->decimal('passing_score', 5, 2)->default(60.00);
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->unsignedInteger('questions_per_exam')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('subject_id');
            $table->index('year_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
