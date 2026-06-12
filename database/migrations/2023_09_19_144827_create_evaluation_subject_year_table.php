<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationSubjectYearTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluation_subject_year', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('evaluation_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedInteger('year');
            $table->timestamps();

            $table->foreign('evaluation_id')->references('id')->on('evaluations')->onDelete('set null');;
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');

            $table->unique(['evaluation_id', 'subject_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluation_subject_year');
    }
}
