<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentSectionChangeHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('student_section_change_history', function (Blueprint $table) {
            $table->id();

            // Estudiante
            $table->unsignedBigInteger('student_id');

            // Sección y sub-grupo ANTERIOR
            $table->unsignedBigInteger('old_section_id')->nullable();
            $table->string('old_sub_grupo')->nullable();

            // Sección y sub-grupo NUEVO
            $table->unsignedBigInteger('new_section_id')->nullable();
            $table->string('new_sub_grupo')->nullable();

            // Profesor que llevaba al estudiante ANTES del cambio
            $table->unsignedBigInteger('old_user_id')->nullable();

            // Año académico
            $table->unsignedBigInteger('year_id');

            // Fecha del cambio
            $table->timestamp('fecha_registro')->useCurrent();

            // Comentario general
            $table->string('comentario')->nullable();

            // Solo esta FK es obligatoria
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_section_change_history');
    }
}
