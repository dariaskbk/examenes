<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarnetConfigsTable extends Migration
{
    public function up()
    {
        Schema::create('carnet_configs', function (Blueprint $table) {
            $table->id();
            $table->string('background_image')->nullable(); // Ruta de la imagen de fondo
            $table->json('elements_positions')->nullable(); // Posiciones de los elementos
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('carnet_configs');
    }
}
