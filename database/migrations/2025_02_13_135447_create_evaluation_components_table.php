<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationComponentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluation_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_subject_year_id')->nullable();
            $table->string('name')->nullable();
            $table->decimal('value', 5, 2)->nullable();
            $table->decimal('max_points', 8, 2)->nullable();
            $table->unsignedBigInteger('period_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('evaluation_subject_year_id')
                ->references('id')->on('evaluation_subject_year')
                ->onDelete('cascade');

            $table->foreign('period_id')
                ->references('id')
                ->on('periods')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('section_id')
                ->references('id')
                ->on('sections')
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
        Schema::dropIfExists('evaluation_components');
    }
}
