<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('justification_details', function (Blueprint $table) {
            $table->id();

            // Relación con la justificación principal
            $table->foreignId('justification_id')
                ->constrained('absence_justifications')
                ->onDelete('cascade');

            // Profesor responsable
            $table->foreignId('user_teacher_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Asistencias relacionadas (múltiples)
            $table->json('attendance_ids');

            // Estado
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Auditoría
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('observation')->nullable();

            $table->timestamps();

            // Índices para rendimiento
            $table->index('justification_id');
            $table->index('user_teacher_id');
            $table->index('status');
            $table->index('reviewed_by');

            // ✅ Eliminado: unique(['justification_id', 'user_teacher_id'])
            // Ahora permitimos múltiples detalles por profesor en la misma justificación
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justification_details');
    }
};
