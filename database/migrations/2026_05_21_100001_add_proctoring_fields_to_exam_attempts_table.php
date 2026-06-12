<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            // Headline metric: number of times the student left the exam screen/tab
            $table->unsignedInteger('focus_loss_count')->default(0)->after('ip_address');
            // Detailed incident log: [{type, at}, ...]
            $table->json('cheat_flags')->nullable()->after('focus_loss_count');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['focus_loss_count', 'cheat_flags']);
        });
    }
};
