<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // Índice para ORDER BY last_name_1
            $table->index('last_name_1', 'idx_students_last_name_1');
        });

        Schema::table('sections', function (Blueprint $table) {
            // Índice para ORDER BY name
            $table->index('name', 'idx_sections_name');
        });

        Schema::table('section_student_year', function (Blueprint $table) {
             // Índice adicional para búsquedas por año (complementa el unique existente)
             $table->index(['year', 'student_id'], 'idx_year_student');
         });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_last_name_1');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropIndex('idx_sections_name');
        });

         Schema::table('section_student_year', function (Blueprint $table) {
             $table->dropIndex('idx_year_student');
         });
    }
};
