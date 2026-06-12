<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGradeComponentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grade_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->unsignedBigInteger('evaluation_component_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->double('grade', 8, 2)->nullable();
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->foreign('grade_id')
                ->references('id')->on('grades')
                ->onDelete('cascade');

            $table->foreign('evaluation_component_id')
                ->references('id')->on('evaluation_components')
                ->onDelete('cascade');

            $table->foreign('student_id')
                ->references('id')->on('students')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grade_components');
    }
}
