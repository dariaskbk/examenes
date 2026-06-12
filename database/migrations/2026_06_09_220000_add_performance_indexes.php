<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for the four hottest read paths.
 *
 * Each composite is chosen to cover both the WHERE filter and the ORDER BY,
 * eliminating filesort/temp table on the most frequent queries.
 *
 * All adds are additive and safe to roll out without downtime.
 */
return new class extends Migration
{
    public function up(): void
    {
        // (1) exam_questions: WHERE exam_id=? ORDER BY `order`
        //     Replaces the FK-only index with a composite that also satisfies the sort.
        //     Eliminates the FULL SCAN + filesort we see today.
        Schema::table('exam_questions', function (Blueprint $t) {
            $t->index(['exam_id', 'order'], 'exam_questions_exam_id_order_index');
        });

        // (2)+(3) exam_attempts: WHERE exam_id=? AND status=? ORDER BY submitted_at/started_at
        //     One composite for results-page sort by submitted_at.
        Schema::table('exam_attempts', function (Blueprint $t) {
            $t->index(['exam_id', 'status', 'submitted_at'], 'exam_attempts_exam_status_submitted_index');
            // Monitor polling: status='in_progress' sorted by started_at
            $t->index(['exam_id', 'status', 'started_at'], 'exam_attempts_exam_status_started_index');
        });

        // (4) exams: dashboard query (mis activos sorted by created_at)
        //     The existing exams_user_id_index leaves filesort behind.
        Schema::table('exams', function (Blueprint $t) {
            $t->index(['user_id', 'archived_at', 'created_at'], 'exams_user_archived_created_index');
        });

        // (Bonus) exam_shares: "invitaciones pendientes para mí"
        //     The single to_user_id index is fine for small N, but composite covers it cleanly.
        Schema::table('exam_shares', function (Blueprint $t) {
            $t->index(['to_user_id', 'status'], 'exam_shares_to_user_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', fn(Blueprint $t) => $t->dropIndex('exam_questions_exam_id_order_index'));
        Schema::table('exam_attempts', function (Blueprint $t) {
            $t->dropIndex('exam_attempts_exam_status_submitted_index');
            $t->dropIndex('exam_attempts_exam_status_started_index');
        });
        Schema::table('exams', fn(Blueprint $t) => $t->dropIndex('exams_user_archived_created_index'));
        Schema::table('exam_shares', fn(Blueprint $t) => $t->dropIndex('exam_shares_to_user_status_index'));
    }
};
