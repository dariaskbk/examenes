<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Añadir campos para saber quién leyó el mensaje
            $table->boolean('read_by_teacher')->default(false)->after('is_read');
            $table->boolean('read_by_guardian')->default(false)->after('read_by_teacher');

            // Opcional: puedes eliminar el campo is_read si ya no lo usas
            // $table->dropColumn('is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['read_by_teacher', 'read_by_guardian']);

            // Si eliminaste is_read, vuelve a crearlo
            // $table->boolean('is_read')->default(false)->after('sender_id');
        });
    }
};
