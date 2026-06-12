<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('user_guardian_id');
            $table->unsignedBigInteger('user_teacher_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Llaves foráneas
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('user_guardian_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('user_teacher_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversations');
    }
}
