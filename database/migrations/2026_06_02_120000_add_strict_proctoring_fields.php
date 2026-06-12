<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-exam opt-in for strict proctoring (blocking on screen leave)
        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('proctoring_strict')->default(false)->after('proctoring');
            $table->unsignedTinyInteger('proctoring_threshold')->default(2)->after('proctoring_strict');
        });

        // Attempt-level pause state
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('focus_loss_count');
        });

        // Per-answer voided flag (teacher action: anular pregunta)
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->boolean('voided')->default(false)->after('points_earned');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['proctoring_strict', 'proctoring_threshold']);
        });
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('paused_at');
        });
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->dropColumn('voided');
        });
    }
};
