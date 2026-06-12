<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMisconductTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('misconduct_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('misconduct_id')->constrained()->onDelete('cascade');
            $table->string('description')->nullable();
            $table->integer('year')->nullable();
            $table->foreignId('period_id')->constrained()->onDelete('cascade');
            $table->boolean('status')->default(true);
            $table->string('delete_comment')->nullable();
            $table->integer('user_delete')->nullable();
            $table->boolean('contumacy')->default(false);
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
        Schema::dropIfExists('misconduct_tickets');
    }
}
