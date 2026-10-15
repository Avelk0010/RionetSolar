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
        Schema::create('solar_project_system_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solar_project_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('solar_panel_equipment_id')->constrained('electrical_equipment')->restrictOnDelete();
            $table->foreignId('inverter_equipment_id')->constrained('electrical_equipment')->restrictOnDelete();
            $table->foreignId('battery_equipment_id')->constrained('electrical_equipment')->restrictOnDelete();
            $table->decimal('total_oversizing_percent', 5, 2);
            $table->decimal('night_oversizing_percent', 5, 2);
            $table->decimal('max_battery_discharge_percent', 5, 2);
            $table->unsignedInteger('panel_count')->nullable();
            $table->unsignedInteger('inverter_count')->nullable();
            $table->unsignedInteger('battery_count')->nullable();
            $table->unsignedInteger('panels_per_string')->nullable();
            $table->decimal('array_power_w', 14, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_project_system_designs');
    }
};
