<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_attendance_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('special_attendance_id')
                ->constrained('special_attendances')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('role', 20)->default('PROCTOR');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['special_attendance_id', 'user_id'],
                'sa_user_att_user_unique'
            );

            $table->index(
                ['user_id', 'special_attendance_id'],
                'sa_user_user_att_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_attendance_user');
    }
};
