<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentSectionChangeBackupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_section_change_backups', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('history_id')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('year_id');
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->string('table_name');
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('action', 20); // update | delete | insert

            $table->json('payload_before')->nullable();
            $table->json('payload_after')->nullable();

            $table->boolean('is_restored')->default(false);
            $table->timestamp('restored_at')->nullable();
            $table->unsignedBigInteger('restored_by')->nullable();

            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('history_id')
                ->references('id')
                ->on('student_section_change_history')
                ->nullOnDelete();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();

            $table->foreign('year_id')
                ->references('id')
                ->on('years')
                ->cascadeOnDelete();

            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects')
                ->nullOnDelete();

            $table->foreign('restored_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_section_change_backups');
    }
}
