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
        Schema::create('student_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_id')->constrained('years')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('alert_type_id')->constrained('alert_types')->onDelete('restrict');

            // Usuario que creó la alerta (null si es automática)
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Usuario asignado para atender la alerta
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Estado de la alerta
            $table->enum('status', ['activada', 'en_proceso', 'en_espera', 'cerrada'])->default('activada');

            // Prioridad
            $table->enum('priority', ['baja', 'media', 'alta', 'critica'])->default('media');

            // Fechas
            $table->date('activation_date'); // Fecha en que se detectó/registró
            $table->date('closure_date')->nullable(); // Fecha en que se cerró

            // Descripción detallada de la situación
            $table->text('description');

            // Si fue generada automáticamente
            $table->boolean('automatic_trigger')->default(false);

            // Si se debe notificar a los encargados
            $table->boolean('notify_guardians')->nullable();

            // Datos específicos del trigger automático (JSON)
            // Para ausencias: fechas, materias, total, etc.
            $table->json('trigger_data')->nullable();

            // Correos adicionales para notificar (JSON array)
            $table->json('additional_emails')->nullable();

            // Notas de cierre
            $table->text('closure_notes')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Por si se requiere eliminar pero mantener histórico

            // Índices para optimizar consultas
            $table->index(['year_id', 'student_id']);
            $table->index(['year_id', 'status']);
            $table->index(['year_id', 'assigned_to_user_id']);
            $table->index('activation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_alerts');
    }
};
