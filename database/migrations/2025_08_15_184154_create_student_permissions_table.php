<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentPermissionsTable extends Migration
{
    public function up()
    {
        Schema::create('student_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('guardian_id')->constrained('guardians')->onDelete('cascade');

            // Guardar varias fechas como JSON
            $table->json('permission_dates');

            $table->time('exit_time')->nullable();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('permission_type', ['exit', 'uniform']);
            $table->foreignId('period_id')->constrained('periods');
            $table->text('observation')->nullable();
            $table->foreignId('year_id')->constrained('years');

            // Quién revisó (aprobó o rechazó)
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_permissions');
    }
}
