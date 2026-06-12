<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Optional link to a SICORE evaluation_component (TESTS rubro).
            // Null = formative practice (no grade sent to SICORE).
            $table->unsignedBigInteger('evaluation_component_id')->nullable()->after('level_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('evaluation_component_id');
        });
    }
};
