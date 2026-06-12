<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddFinalNotificationFieldsToAttendanceDeletionLogsTable extends Migration
{
    public function up()
    {
        Schema::table('attendance_deletion_logs', function (Blueprint $table) {
            $table->timestamp('finalized_notified_at')->nullable()->after('restore_reason');
            $table->unsignedInteger('finalized_notifications_count')->default(0)->after('finalized_notified_at');
        });

        DB::table('attendance_deletion_logs')
            ->whereNull('finalized_notified_at')
            ->update([
                'finalized_notified_at' => now(),
                'finalized_notifications_count' => 0,
                'updated_at' => now(),
            ]);
    }

    public function down()
    {
        Schema::table('attendance_deletion_logs', function (Blueprint $table) {
            $table->dropColumn(['finalized_notified_at', 'finalized_notifications_count']);
        });
    }
}
