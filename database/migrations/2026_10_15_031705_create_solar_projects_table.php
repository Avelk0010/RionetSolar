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
        Schema::create('solar_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('solar_hours_per_day', 5, 2);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('total_consumption_wh_per_day', 14, 2)->default(0);
            $table->decimal('night_consumption_wh_per_day', 14, 2)->default(0);
            $table->decimal('maximum_hourly_power_w', 14, 2)->nullable();
            $table->unsignedSmallInteger('solar_string_count')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_projects');
    }
};
