<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();

            // Relación opcional: si el email pertenece a un user del sistema
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Email suprimido (fuente de verdad)
            $table->string('email')->unique();

            // hard = permanente (no existe / mailbox unavailable)
            // soft = temporal (overquota / errores temporales)
            // manual = agregado manualmente
            $table->enum('type', ['hard', 'soft', 'manual'])->default('manual');

            // Soft suppression: hasta cuándo no se debe enviar
            $table->timestamp('suppressed_until')->nullable();

            // Diagnóstico
            $table->string('bounce_code')->nullable(); // ej: 550-5.1.1, 452-4.2.2
            $table->text('reason')->nullable();

            // Control
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
    }
};
