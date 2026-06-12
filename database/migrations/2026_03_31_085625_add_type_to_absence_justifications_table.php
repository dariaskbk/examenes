<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTypeToAbsenceJustificationsTable extends Migration
{
    public function up()
    {
        Schema::table('absence_justifications', function (Blueprint $table) {
            $table->enum('type', ['absence', 'late'])
                ->default('absence')
                ->after('student_id');
        });

        // Por seguridad, marcar registros viejos como ausencia
        DB::table('absence_justifications')
            ->whereNull('type')
            ->update(['type' => 'absence']);
    }

    public function down()
    {
        Schema::table('absence_justifications', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
