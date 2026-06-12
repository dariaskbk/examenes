<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // encargado
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('year_id')->nullable()->constrained()->nullOnDelete();

            $table->date('absence_date');
            $table->string('email')->nullable();

            $table->string('type')->default('absence_email');
            $table->string('status')->default('pending');
            // pending, queued, sent, blocked, failed, bounced_soft, bounced_hard

            $table->string('blocked_reason')->nullable();
            $table->string('bounce_code')->nullable();
            $table->text('error_message')->nullable();

            $table->string('mail_subject')->nullable();
            $table->longText('mail_html')->nullable();
            $table->longText('mail_text')->nullable();

            $table->unsignedInteger('lessons_count')->default(0);
            $table->text('lessons_detail')->nullable();

            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();

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
        Schema::dropIfExists('attendance_notifications');
    }
}
