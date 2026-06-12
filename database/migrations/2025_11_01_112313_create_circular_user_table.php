<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCircularUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('circular_user', function (Blueprint $table) {
            $table->id();

            // 🔹 Relaciones
            $table->unsignedBigInteger('circular_id'); // ID de la circular
            $table->unsignedBigInteger('user_id');     // ID del usuario receptor (teacher o guardian)

            // 🔹 Estado de lectura
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // 🔹 Llaves foráneas
            $table->foreign('circular_id')
                ->references('id')
                ->on('circulars')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('circular_user');
    }
}
