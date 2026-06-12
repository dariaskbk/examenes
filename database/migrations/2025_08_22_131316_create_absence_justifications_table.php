<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbsenceJustificationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('absence_justifications', function (Blueprint $table) {
            $table->id();

            // Guardian que crea la justificación (nullable)
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->onDelete('cascade');

            // Usuario que crea la justificación (admin, orientador, etc.)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('year_id')->constrained('years')->onDelete('cascade'); // Año escolar
            $table->json('dates'); // ['2025-04-01', '2025-04-02', ...]
            $table->text('reason'); // Motivo de la justificación
            $table->string('proof_path', 255)->nullable(); // Comprobante (PDF, imagen, etc.)
            $table->string('status_summary', 20)->default('pending'); // Resumen del estado
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index('student_id');
            $table->index('guardian_id');
            $table->index('user_id');
            $table->index('year_id');
            $table->index('status_summary');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('absence_justifications');
    }
}
