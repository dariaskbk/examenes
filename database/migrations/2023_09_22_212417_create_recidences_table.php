<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecidencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recidences', function (Blueprint $table) {
            $table->id();
            $table->string('provincia');
            $table->string('canton');
            $table->string('distrito');
            $table->string('direccion_exacta');
            $table->unsignedBigInteger('student_id');
            $table->nullableTimestamps();

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
        Schema::dropIfExists('recidences');
    }
}
