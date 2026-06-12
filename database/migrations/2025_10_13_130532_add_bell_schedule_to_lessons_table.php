<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBellScheduleToLessonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedBigInteger('bell_schedule_id')->nullable()->after('id');
            $table->unsignedInteger('number')->nullable()->after('name'); // 1,2,3...
            $table->boolean('is_break')->default(false)->after('end');

            $table->foreign('bell_schedule_id')->references('id')->on('bell_schedules')->onDelete('cascade');
            $table->unique(['bell_schedule_id','number']); // evita duplicar la lección 1 en la misma rejilla
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['bell_schedule_id']);
            $table->dropColumn('bell_schedule_id');
            $table->dropColumn('number');
            $table->dropColumn('is_break');
        });
    }
}
