<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_nacimiento');
            $table->string('email');
            $table->boolean('afectividad');
            $table->boolean('religion');
            $table->boolean('poliza');
            $table->integer('numero_poliza')->nullable();
            $table->integer('telefono')->nullable();
            $table->date('poliza_rige')->nullable();
            $table->date('poliza_vence')->nullable();
            $table->boolean('enfermedad');
            $table->boolean('apoyo_curricular');
            $table->string('apoyo_tipo')->nullable();
            $table->string('tipo_enfermedades')->nullable();
            $table->boolean('beca');
            $table->string('tipo_beca')->nullable();
            $table->boolean('comedor')->nullable();
            $table->boolean('transporte')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
