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
        Schema::create('solar_project_string_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solar_project_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('string_number');
            $table->unsignedSmallInteger('row_number');
            $table->unsignedSmallInteger('panel_count');
            $table->unique(['solar_project_id', 'string_number', 'row_number'], 'string_rows_project_string_row_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_project_string_rows');
    }
};
