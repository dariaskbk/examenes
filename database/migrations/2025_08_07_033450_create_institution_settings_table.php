<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstitutionSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('institution_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('year_id');
            $table->foreign('year_id')->references('id')->on('years')->onDelete('cascade');
            // Datos del colegio o escuela
            $table->string('name');
            $table->string('circuit')->nullable();
            $table->string('regional_direction')->nullable();
            $table->string('phone')->nullable();
            $table->string('budget_code')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            // Datos del director o Directora
            $table->string('principal_name')->nullable();
            $table->enum('principal_gender', ['masculino', 'femenino', 'otro'])->nullable();
            $table->enum('principal_degree', ['Lic.', 'MSc.', 'Dr.'])->nullable();
            // Imagenes
            $table->string('institution_logo')->nullable();
            $table->string('mep_logo')->nullable();
            $table->string('seal_image')->nullable();

            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('institution_settings');
    }
}
