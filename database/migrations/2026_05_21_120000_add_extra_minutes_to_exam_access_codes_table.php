<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_access_codes', function (Blueprint $table) {
            // Extra exam time (minutes) for students with accommodations
            $table->unsignedSmallInteger('extra_minutes')->default(0)->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('exam_access_codes', function (Blueprint $table) {
            $table->dropColumn('extra_minutes');
        });
    }
};
