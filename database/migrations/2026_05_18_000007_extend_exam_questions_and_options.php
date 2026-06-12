<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->string('audio')->nullable()->after('image');
            $table->string('video')->nullable()->after('audio');
            // none | image | audio | video — declares what media this question REQUIRES
            $table->string('media_type')->default('none')->after('video');
        });

        Schema::table('exam_options', function (Blueprint $table) {
            // Used by 'matching' type: option_text = concept, match_text = correct definition
            $table->text('match_text')->nullable()->after('option_text');
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropColumn(['audio', 'video', 'media_type']);
        });
        Schema::table('exam_options', function (Blueprint $table) {
            $table->dropColumn('match_text');
        });
    }
};
