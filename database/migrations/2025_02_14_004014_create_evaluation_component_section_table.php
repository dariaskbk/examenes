<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationComponentSectionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluation_component_section', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_component_id');
            $table->unsignedBigInteger('section_id');
            $table->timestamps();

            $table->foreign('evaluation_component_id')
                ->references('id')->on('evaluation_components')
                ->onDelete('cascade');

            $table->foreign('section_id')
                ->references('id')->on('sections')
                ->onDelete('cascade');

            $table->unique(['evaluation_component_id', 'section_id'], 'eval_comp_sec_unique');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluation_component_section');
    }
}
