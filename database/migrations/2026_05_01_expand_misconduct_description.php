<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ampliar 'description' de VARCHAR a TEXT en misconduct_tickets.
     */
    public function up(): void
    {
        Schema::table('misconduct_tickets', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('misconduct_tickets', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->change();
        });
    }
};
