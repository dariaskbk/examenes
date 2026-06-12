<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecialAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('year_id')
                ->constrained('years')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('section_id')
                ->nullable()
                ->constrained('sections')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('subject_id')
                ->nullable()
                ->constrained('subjects')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('date');

            $table->string('type', 30); // EXAMEN, PROYECTO, GIRA, ACTO_CIVICO, OTRO
            $table->string('title', 150);
            $table->text('description')->nullable();

            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();

            $table->boolean('requires_signature')->default(false);

            $table->string('status', 15)->default('OPEN'); // OPEN / CLOSED

            $table->string('mass_group_key', 80)->nullable();

            $table->string('evidence_path')->nullable();

            $table->string('group_type', 1)->default('C');
            $table->string('selection_mode', 30)->default('scheduled_section');

            $table->timestamps();

            $table->index(
                ['year_id', 'section_id', 'date'],
                'sa_year_section_date_idx'
            );

            $table->index(
                ['subject_id', 'date'],
                'sa_subject_date_idx'
            );

            $table->index('created_by', 'sa_created_by_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('special_attendances');
    }
}
