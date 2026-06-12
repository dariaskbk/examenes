<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModalityToInstitutionSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('institution_settings', function (Blueprint $table) {
            $table->enum('modality', [
                'Escuela',
                'Unidad Pedagógica',
                'Centro Educativo Integral',
                'Liceo',
                'Colegio Técnico Profesional',
                'Colegio Científico',
                'Colegio Artístico / Deportivo',
                'Colegio Humanístico',
                'Liceo Nocturno, IPEC, CINDEA',
                ])->after('seal_image');
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
            $table->dropColumn('modality');
        });
    }
}
