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
        Schema::create('alert_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_alert_id')->constrained('student_alerts')->onDelete('cascade');

            // Usuario destinatario (puede ser null si es email externo)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // Email destinatario
            $table->string('recipient_email');

            // Tipo de notificación
            $table->enum('notification_type', [
                'alerta_creada',
                'alerta_asignada',
                'cambio_estado',
                'seguimiento_registrado',
                'accion_vencida',
                'alerta_cerrada'
            ]);

            // Canal de notificación
            $table->enum('channel', ['email', 'system', 'both'])->default('both');

            // Estado del envío
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');

            // Fechas
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Mensaje de error si falló
            $table->text('error_message')->nullable();

            // Intentos de envío
            $table->integer('attempts')->default(0);

            $table->timestamps();

            // Índices
            $table->index('student_alert_id');
            $table->index(['user_id', 'read_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_notifications');
    }
};
