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
        Schema::create('alert_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_alert_id')->constrained('student_alerts')->onDelete('cascade');

            // Descripción de la acción a realizar
            $table->text('action_description');

            // Responsable de ejecutar la acción
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Fechas
            $table->date('due_date')->nullable(); // Fecha límite
            $table->date('completion_date')->nullable(); // Fecha de completado

            // Estado de la acción
            $table->enum('status', ['pendiente', 'en_progreso', 'completada', 'cancelada'])->default('pendiente');

            // Notas sobre la ejecución
            $table->text('notes')->nullable();

            // Usuario que creó la acción
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Índices
            $table->index('student_alert_id');
            $table->index(['responsible_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_actions');
    }
};
