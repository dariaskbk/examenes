<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBellSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bell_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('year_id');
            $table->string('name'); // "Académico 2025", "Técnico 2025"
            $table->enum('modality', ['academic', 'technical']);
            $table->smallInteger('lesson_duration'); // Duración de cada lección en ese horario
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('year_id')->references('id')->on('years')->onDelete('cascade');
            $table->unique(['year_id','modality','name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bell_schedules');
    }
}
