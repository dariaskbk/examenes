<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupTypeToEvaluationComponentsTable extends Migration
{
    public function up()
    {
        Schema::table('evaluation_components', function (Blueprint $table) {
            $table->string('group_type')->nullable()->after('section_id');
        });
    }

    public function down()
    {
        Schema::table('evaluation_components', function (Blueprint $table) {
            $table->dropColumn('group_type');
        });
    }

}
