<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circulars', function (Blueprint $table) {
            $table->id();

            // 🔹 Usuario que creó la circular
            $table->unsignedBigInteger('user_id');

            // 🔹 Año lectivo al que pertenece
            $table->unsignedBigInteger('year_id');

            // 🔹 Tipo de destinatario
            $table->enum('target_type', ['teachers', 'guardians']);

            // 🔹 Si aplica a todos los destinatarios de ese tipo
            $table->boolean('for_all')->default(false);

            // 🔹 IDs de destinatarios específicos (profesores o secciones)
            $table->json('target_ids')->nullable();

            // 🔹 Motivo o asunto
            $table->string('reason');

            // 🔹 Ruta del archivo adjunto
            $table->string('file_path');

            $table->timestamps();

            // 🔹 Relaciones
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('year_id')->references('id')->on('years')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circulars');
    }
};
