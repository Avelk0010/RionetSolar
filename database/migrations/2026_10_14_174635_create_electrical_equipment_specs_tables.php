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
        Schema::create('inverter_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electrical_equipment_id')->unique()->constrained('electrical_equipment')->cascadeOnDelete();
            $table->unsignedSmallInteger('mppt_count');
            $table->decimal('mppt_max_voltage_v', 12, 3);
            $table->decimal('mppt_max_current_a', 12, 3);
            $table->decimal('mppt_max_power_w', 12, 2);
            $table->decimal('battery_charge_voltage_v', 12, 3);
            $table->unsignedSmallInteger('ac_input_voltage_v');
            $table->unsignedSmallInteger('ac_output_voltage_v');
            $table->decimal('max_output_current_a', 12, 3);
            $table->decimal('max_output_power_w', 12, 2);
            $table->timestamps();
        });

        Schema::create('solar_panel_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electrical_equipment_id')->unique()->constrained('electrical_equipment')->cascadeOnDelete();
            $table->decimal('max_power_w', 12, 2);
            $table->decimal('open_circuit_voltage_v', 12, 3);
            $table->decimal('short_circuit_current_a', 12, 3);
            $table->decimal('max_system_voltage_v', 12, 3);
            $table->decimal('length_mm', 12, 2);
            $table->decimal('width_mm', 12, 2);
            $table->decimal('height_mm', 12, 2);
            $table->timestamps();
        });

        Schema::create('battery_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electrical_equipment_id')->unique()->constrained('electrical_equipment')->cascadeOnDelete();
            $table->decimal('nominal_power_w', 12, 2)->nullable();
            $table->decimal('capacity_ah', 12, 3);
            $table->decimal('voltage_v', 12, 3);
            $table->timestamps();
        });

        Schema::create('dc_breaker_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electrical_equipment_id')->unique()->constrained('electrical_equipment')->cascadeOnDelete();
            $table->decimal('min_operating_voltage_v', 12, 3);
            $table->decimal('max_operating_voltage_v', 12, 3);
            $table->decimal('rated_current_a', 12, 3);
            $table->timestamps();
        });

        Schema::create('ac_breaker_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electrical_equipment_id')->unique()->constrained('electrical_equipment')->cascadeOnDelete();
            $table->unsignedSmallInteger('operating_voltage_v');
            $table->decimal('rated_current_a', 12, 3);
            $table->timestamps();
        });

        Schema::create('solar_cable_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electrical_equipment_id')->unique()->constrained('electrical_equipment')->cascadeOnDelete();
            $table->string('awg_gauge', 8);
            $table->decimal('diameter_mm', 12, 3);
            $table->decimal('nominal_voltage_dc_v', 12, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_cable_specs');
        Schema::dropIfExists('ac_breaker_specs');
        Schema::dropIfExists('dc_breaker_specs');
        Schema::dropIfExists('battery_specs');
        Schema::dropIfExists('solar_panel_specs');
        Schema::dropIfExists('inverter_specs');
    }
};
