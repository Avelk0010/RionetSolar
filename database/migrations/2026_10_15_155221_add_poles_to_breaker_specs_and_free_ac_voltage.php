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
        Schema::table('dc_breaker_specs', function (Blueprint $table) {
            $table->unsignedTinyInteger('poles')->after('electrical_equipment_id');
        });

        Schema::table('ac_breaker_specs', function (Blueprint $table) {
            $table->unsignedTinyInteger('poles')->after('electrical_equipment_id');
            $table->decimal('operating_voltage_v', 12, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ac_breaker_specs', function (Blueprint $table) {
            $table->dropColumn('poles');
            $table->unsignedSmallInteger('operating_voltage_v')->change();
        });

        Schema::table('dc_breaker_specs', function (Blueprint $table) {
            $table->dropColumn('poles');
        });
    }
};
