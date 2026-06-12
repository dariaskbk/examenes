<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceNotificationAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_notification_attendance', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('attendance_notification_id');
            $table->unsignedBigInteger('attendance_id');

            $table->foreign('attendance_notification_id', 'ana_notification_fk')
                ->references('id')
                ->on('attendance_notifications')
                ->onDelete('cascade');

            $table->foreign('attendance_id', 'ana_attendance_fk')
                ->references('id')
                ->on('attendances')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(
                ['attendance_notification_id', 'attendance_id'],
                'ana_notification_attendance_unique'
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
        Schema::dropIfExists('attendance_notification_attendance');
    }
}
