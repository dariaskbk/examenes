<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDesglosableToEvaluationSubjectYearTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('evaluation_subject_year', function (Blueprint $table) {
            $table->boolean('desglosable')->default(false)->nullable()->after('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('evaluation_subject_year', function (Blueprint $table) {
            $table->dropColumn('desglosable');
        });
    }
}
