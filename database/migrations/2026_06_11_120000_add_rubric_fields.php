<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Structured rubric (criteria × levels) on the question
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->json('rubric')->nullable()->after('grading_criteria');
        });
        // Teacher's per-criterion selections when grading
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->json('grading_choices')->nullable()->after('feedback');
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropColumn('rubric');
        });
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->dropColumn('grading_choices');
        });
    }
};
