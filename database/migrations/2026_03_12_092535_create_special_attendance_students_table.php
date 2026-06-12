<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecialAttendanceStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_attendance_students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('special_attendance_id')
                ->constrained('special_attendances')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('status', 1)->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->text('observation')->nullable();

            $table->timestamps();

            $table->unique(
                ['special_attendance_id', 'student_id'],
                'sa_students_att_student_unique'
            );

            $table->index(
                ['student_id', 'special_attendance_id'],
                'sa_students_student_att_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('special_attendance_students');
    }
}
