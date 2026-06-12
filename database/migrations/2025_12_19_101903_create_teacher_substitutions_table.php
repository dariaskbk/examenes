<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_substitutions', function (Blueprint $table) {
            $table->id();

            // Año lectivo
            $table->foreignId('year_id')
                ->constrained('years')
                ->cascadeOnDelete();

            // Profesor incapacitado (oficial)
            $table->foreignId('teacher_original_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Profesor suplente
            $table->foreignId('teacher_substitute_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Fechas
            $table->date('start_date');
            $table->date('end_date');

            // Detalles
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            // Estado
            $table->boolean('active')->default(true);

            // Auditoría
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Índice útil
            $table->index(
                ['year_id', 'teacher_substitute_id', 'start_date', 'end_date'],
                'substitute_year_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_substitutions');
    }
};
