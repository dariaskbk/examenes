<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('evaluation_component_deletion_audits');

        Schema::create('evaluation_component_deletion_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_component_id')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->unsignedInteger('grade_components_count')->default(0);
            $table->json('component_snapshot');
            $table->json('grade_components_snapshot')->nullable();
            $table->json('rubric_snapshot')->nullable();
            $table->json('sections_snapshot')->nullable();
            $table->timestamp('deleted_at');
            $table->timestamps();

            $table->index('evaluation_component_id', 'ec_del_audit_component_idx');
            $table->index('deleted_by', 'ec_del_audit_user_idx');

            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_component_deletion_audits');
    }
};
