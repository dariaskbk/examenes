<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minutas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20);           // N°01-2025
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // docente
            $table->json('lesson_ids');             // [1, 2, 3]
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->json('puntos')->nullable();     // [{titulo, descripcion}]
            $table->text('acuerdos')->nullable();
            $table->timestamps();

            // Un docente no puede crear dos minutas para la misma sección/materia/fecha
            $table->unique(['section_id', 'subject_id', 'user_id', 'fecha'], 'minuta_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minutas');
    }
};
