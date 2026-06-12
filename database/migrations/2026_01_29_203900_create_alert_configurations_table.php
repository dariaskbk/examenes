<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_id')->constrained('years')->onDelete('cascade');
            $table->boolean('auto_absence_alerts')->default(true);
            $table->json('absence_thresholds')->nullable();
            $table->integer('consecutive_absences_threshold')->default(5);
            $table->integer('consecutive_absences_period')->default(15);
            $table->json('category_responsibles')->nullable();
            $table->timestamps();
            $table->unique('year_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_configurations');
    }
};
