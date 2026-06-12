<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRestoreFieldsToAttendanceDeletionLogsTable extends Migration
{
    public function up()
    {
        Schema::table('attendance_deletion_logs', function (Blueprint $table) {
            $table->timestamp('restored_at')->nullable()->after('reason');
            $table->foreignId('restored_by')->nullable()->after('restored_at')->constrained('users')->nullOnDelete();
            $table->text('restore_reason')->nullable()->after('restored_by');
        });
    }

    public function down()
    {
        Schema::table('attendance_deletion_logs', function (Blueprint $table) {
            $table->dropForeign(['restored_by']);
            $table->dropColumn(['restored_at', 'restored_by', 'restore_reason']);
        });
    }
}
