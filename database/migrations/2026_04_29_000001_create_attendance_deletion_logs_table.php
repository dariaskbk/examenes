<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceDeletionLogsTable extends Migration
{
    public function up()
    {
        Schema::create('attendance_deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->date('attendance_date');
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('records_count')->default(0);
            $table->json('attendance_ids');
            $table->json('student_ids');
            $table->json('lesson_ids');
            $table->json('notification_ids')->nullable();
            $table->json('records_snapshot');
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_deletion_logs');
    }
}
