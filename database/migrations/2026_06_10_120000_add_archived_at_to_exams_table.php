<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Soft-archive: archived exams are hidden from the main list but
            // still fully queryable (results, codes, sync, etc. all keep working).
            $table->timestamp('archived_at')->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
