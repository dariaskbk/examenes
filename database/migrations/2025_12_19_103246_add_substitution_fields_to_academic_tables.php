<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        // ============================
        // section_user_year
        // ============================
        Schema::table('section_user_year', function (Blueprint $table) {
            $table->boolean('is_substitution')
                ->default(false)
                ->after('year');

            $table->foreignId('teacher_substitution_id')
                ->nullable()
                ->after('is_substitution')
                ->constrained('teacher_substitutions')
                ->nullOnDelete();
        });

        // ============================
        // user_subject_year
        // ============================
        Schema::table('user_subject_year', function (Blueprint $table) {
            $table->boolean('is_substitution')
                ->default(false)
                ->after('year_id');

            $table->foreignId('teacher_substitution_id')
                ->nullable()
                ->after('is_substitution')
                ->constrained('teacher_substitutions')
                ->nullOnDelete();
        });

        // ============================
        // schedules
        // ============================
        Schema::table('schedules', function (Blueprint $table) {
            $table->boolean('is_substitution')
                ->default(false)
                ->after('group_type');

            $table->foreignId('teacher_substitution_id')
                ->nullable()
                ->after('is_substitution')
                ->constrained('teacher_substitutions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('section_user_year', function (Blueprint $table) {
            $table->dropForeign(['teacher_substitution_id']);
            $table->dropColumn(['is_substitution', 'teacher_substitution_id']);
        });

        Schema::table('user_subject_year', function (Blueprint $table) {
            $table->dropForeign(['teacher_substitution_id']);
            $table->dropColumn(['is_substitution', 'teacher_substitution_id']);
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['teacher_substitution_id']);
            $table->dropColumn(['is_substitution', 'teacher_substitution_id']);
        });
    }
};
