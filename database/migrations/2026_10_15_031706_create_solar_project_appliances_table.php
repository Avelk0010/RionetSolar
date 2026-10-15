<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solar_project_appliances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solar_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appliance_id')->constrained()->restrictOnDelete();
            $table->decimal('appliance_energy_consumption_wh', 12, 2);
            $table->unsignedInteger('quantity_total');
            $table->unsignedInteger('quantity_night');
            $table->decimal('usage_hours_total', 4, 2);
            $table->decimal('usage_hours_night', 4, 2);
            $table->decimal('daily_energy_wh', 14, 2);
            $table->decimal('night_energy_wh', 14, 2);
            $table->decimal('peak_power_w', 14, 2);
            $table->unique(['solar_project_id', 'appliance_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_project_appliances');
    }
};
