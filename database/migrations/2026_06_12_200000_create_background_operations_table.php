<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lightweight status tracker for queued operations (sync grades, import
 * questions, etc.). The Job updates this row on start/finish/fail; the UI
 * polls it (or the user reloads the page) to see the result.
 *
 * Keep it generic: type + payload + result + status. One row per dispatch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('background_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('exam_id')->nullable()->constrained('exams')->cascadeOnDelete();
            $t->string('type', 64);                  // 'sync_grades', 'import_questions', …
            $t->enum('status', ['pending','running','done','failed'])->default('pending');
            $t->json('payload')->nullable();         // input params (file path, etc.)
            $t->json('result')->nullable();          // success summary or error details
            $t->string('message', 500)->nullable();  // human-readable status line
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();

            $t->index(['user_id','type','status']);
            $t->index(['exam_id','type','created_at']); // for "latest op per exam" lookups
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('background_operations');
    }
};
