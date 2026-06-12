<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConvocatoriasFielToStudentSubjectYearTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_subject_year', function (Blueprint $table) {
            $table->string('convocatoria_1')->nullable();
            $table->string('convocatoria_2')->nullable();
            $table->string('estrategia_promocion')->nullable();
            $table->boolean('act_ep')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_subject_year', function (Blueprint $table) {
            $table->dropColumn('convocatoria_1');
            $table->dropColumn('convocatoria_2');
            $table->dropColumn('estrategia_promocion');
            $table->dropColumn('act_ep');
        });
    }
}
