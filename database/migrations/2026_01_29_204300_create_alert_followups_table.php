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
        Schema::create('alert_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_alert_id')->constrained('student_alerts')->onDelete('cascade');

            // Usuario que registra el seguimiento
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Fecha del seguimiento
            $table->date('followup_date');

            // Tipo de seguimiento
            $table->enum('followup_type', [
                'observacion',
                'intervencion',
                'reunion',
                'contacto_externo',
                'cambio_estado',
                'otro'
            ])->default('observacion');

            // Notas del seguimiento
            $table->text('notes');

            // Estado anterior y nuevo (para trazabilidad)
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();

            // Si hubo contacto con entidad externa
            $table->string('external_entity')->nullable(); // IMAS, PANI, etc.

            $table->timestamps();

            // Índices
            $table->index('student_alert_id');
            $table->index('followup_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_followups');
    }
};
