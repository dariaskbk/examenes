<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Enable anti-cheat monitoring (tab/focus tracking, copy-paste lock)
            $table->boolean('proctoring')->default(true)->after('show_correct_answers');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('proctoring');
        });
    }
};
