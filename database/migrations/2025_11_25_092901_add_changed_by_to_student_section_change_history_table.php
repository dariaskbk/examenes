<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChangedByToStudentSectionChangeHistoryTable extends Migration
{
    public function up()
    {
        Schema::table('student_section_change_history', function (Blueprint $table) {
            $table->unsignedBigInteger('changed_by')
                ->nullable()
                ->after('new_sub_grupo');  // posición recomendada
        });
    }

    public function down()
    {
        Schema::table('student_section_change_history', function (Blueprint $table) {
            $table->dropColumn('changed_by');
        });
    }

}
