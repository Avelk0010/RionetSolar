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
        Schema::table('solar_panel_specs', function (Blueprint $table) {
            $table->decimal('max_power_voltage_v', 12, 3)->after('max_power_w');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_panel_specs', function (Blueprint $table) {
            $table->dropColumn('max_power_voltage_v');
        });
    }
};
