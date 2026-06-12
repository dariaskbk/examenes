<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRestoreFieldsToEvaluationComponentDeletionAuditsTable extends Migration
{
    public function up()
    {
        Schema::table('evaluation_component_deletion_audits', function (Blueprint $table) {
            $table->unsignedBigInteger('restored_component_id')->nullable()->after('evaluation_component_id');
            $table->unsignedBigInteger('restored_by')->nullable()->after('deleted_by');
            $table->timestamp('restored_at')->nullable()->after('deleted_at');

            $table->index('restored_component_id', 'ec_del_audit_restored_component_idx');
            $table->index('restored_by', 'ec_del_audit_restored_by_idx');

            $table->foreign('restored_by', 'ec_del_audit_restored_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('evaluation_component_deletion_audits', function (Blueprint $table) {
            $table->dropForeign('ec_del_audit_restored_by_fk');
            $table->dropIndex('ec_del_audit_restored_component_idx');
            $table->dropIndex('ec_del_audit_restored_by_idx');
            $table->dropColumn(['restored_component_id', 'restored_by', 'restored_at']);
        });
    }
}
