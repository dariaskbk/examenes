<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change ENUM to VARCHAR so all question types fit without truncation.
        // Old ENUM: multiple_choice | true_false | short_answer
        // New types: single_choice | multiple_select | matching | ordering (+ keep old ones)
        DB::statement("ALTER TABLE exam_questions MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'single_choice'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE exam_questions MODIFY COLUMN `type` ENUM('multiple_choice','true_false','short_answer') NOT NULL DEFAULT 'multiple_choice'");
    }
};
