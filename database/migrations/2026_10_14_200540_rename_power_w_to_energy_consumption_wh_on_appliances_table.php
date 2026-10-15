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
        Schema::table('appliances', function (Blueprint $table) {
            $table->renameColumn('power_w', 'energy_consumption_wh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appliances', function (Blueprint $table) {
            $table->renameColumn('energy_consumption_wh', 'power_w');
        });
    }
};
