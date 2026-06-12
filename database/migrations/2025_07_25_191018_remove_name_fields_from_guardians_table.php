<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveNameFieldsFromGuardiansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropColumn(['name', 'last_name_1', 'last_name_2']);
        });
    }

    public function down()
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->string('name');
            $table->string('last_name_1');
            $table->string('last_name_2');
        });
    }
}
