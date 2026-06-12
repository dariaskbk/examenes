<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_settings', function (Blueprint $table) {
            $table->string('reglamento_url')->nullable()->after('external_mep_logo');
        });
    }

    public function down(): void
    {
        Schema::table('institution_settings', function (Blueprint $table) {
            $table->dropColumn('reglamento_url');
        });
    }
};
