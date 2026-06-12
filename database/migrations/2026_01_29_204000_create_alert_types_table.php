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
        Schema::create('alert_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // AT-AUS-001, AT-SOC-001, etc.
            $table->string('name'); // Nombre descriptivo
            $table->enum('category', [
                'ausentismo',
                'socioeconomicas',
                'familiares',
                'salud',
                'educativas',
                'tecnologicas',
                'convivencia',
                'otras'
            ]);
            $table->text('description')->nullable();

            // Indica si requiere protocolo específico (REA, Reincorporación, etc.)
            $table->string('protocol_reference')->nullable(); // "REA Art.15", "Reincorporación", etc.

            // Indica si la alerta es automática o manual
            $table->boolean('is_automatic')->default(false);

            // Entidad externa sugerida (IMAS, PANI, CCSS, etc.)
            $table->string('suggested_external_entity')->nullable();

            // Activo/Inactivo (por si el MEP cambia lineamientos)
            $table->boolean('is_active')->default(true);

            // Si es tipo personalizado por institución
            $table->boolean('is_custom')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_types');
    }
};
