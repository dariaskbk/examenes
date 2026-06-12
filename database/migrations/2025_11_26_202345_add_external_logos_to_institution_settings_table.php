<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExternalLogosToInstitutionSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('institution_settings', function (Blueprint $table) {
            $table->string("external_mep_logo")->nullable()->after("mep_logo");
            $table->string("external_institution_logo")->nullable()->after("institution_logo");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('institution_settings', function (Blueprint $table) {
            $table->dropColumn("external_mep_logo");
            $table->dropColumn("external_institution_logo");
        });
    }
}
