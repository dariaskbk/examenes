<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentSectionChangeHistoryDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('student_section_change_history_details', function (Blueprint $table) {
            $table->id();

            // Relación con la tabla principal
            $table->unsignedBigInteger('history_id');

            // Materia afectada (OBLIGATORIO guardar aquí, no en el encabezado)
            $table->unsignedBigInteger('subject_id')->nullable();

            // Información de la nota en ese momento
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->decimal('nota', 5, 2)->nullable();
            $table->unsignedBigInteger('evaluation_id')->nullable();

            // Profesor que registró la nota antes del cambio
            $table->unsignedBigInteger('profesor_id')->nullable();

            // Sección donde el estudiante estaba cuando recibió esta nota
            $table->unsignedBigInteger('section_id')->nullable();

            // Fecha real de cuando se registró la nota
            $table->timestamp('fecha_nota')->nullable();

            // Comentario adicional
            $table->string('comentario')->nullable();

            // Relación
            $table->foreign('history_id')->references('id')->on('student_section_change_history')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_section_change_history_details');
    }
}
