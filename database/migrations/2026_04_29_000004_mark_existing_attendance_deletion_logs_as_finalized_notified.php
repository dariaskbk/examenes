<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MarkExistingAttendanceDeletionLogsAsFinalizedNotified extends Migration
{
    public function up()
    {
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
        // Data migration: intentionally not reversible.
    }
}
