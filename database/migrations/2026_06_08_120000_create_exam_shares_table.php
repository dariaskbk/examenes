<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');                  // original exam
            $table->unsignedBigInteger('from_user_id');             // who shared
            $table->unsignedBigInteger('to_user_id');               // who is invited
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('message')->nullable();
            $table->unsignedBigInteger('accepted_exam_id')->nullable(); // clone id, set on accept
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->unique(['exam_id', 'to_user_id']); // no duplicate invites
            $table->index('to_user_id');
            $table->index('from_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_shares');
    }
};
