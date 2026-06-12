<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Maps one exam to N SICORE evaluation components (one per section/group).
        Schema::create('exam_evaluation_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('evaluation_component_id');
            $table->timestamps();

            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            // A component can be linked to at most one exam
            $table->unique('evaluation_component_id');
            $table->index('exam_id');
        });

        // Replace the earlier single-link column with the pivot above
        if (Schema::hasColumn('exams', 'evaluation_component_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropColumn('evaluation_component_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_evaluation_components');
        if (!Schema::hasColumn('exams', 'evaluation_component_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->unsignedBigInteger('evaluation_component_id')->nullable()->index();
            });
        }
    }
};
