<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alert_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_alert_id')->constrained('student_alerts')->onDelete('cascade');

            // Usuario que subió el archivo
            $table->foreignId('uploaded_by_user_id')->constrained('users')->onDelete('cascade');

            // Información del archivo
            $table->string('file_name'); // Nombre original
            $table->string('file_path'); // Ruta en storage
            $table->string('file_type')->nullable(); // image/pdf/document
            $table->integer('file_size')->nullable(); // En bytes

            // Descripción del adjunto
            $table->text('description')->nullable();

            $table->timestamps();

            // Índices
            $table->index('student_alert_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_attachments');
    }
};
