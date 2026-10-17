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
        Schema::create('solar_project_quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solar_project_quote_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->foreignId('electrical_equipment_id')->nullable()->constrained('electrical_equipment')->nullOnDelete();
            $table->foreignId('installation_material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('concept')->nullable();
            $table->unsignedInteger('base_quantity');
            $table->integer('adjustment');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 14, 2);
            $table->decimal('tax_percent', 5, 2);
            $table->decimal('discount_percent', 5, 2);
            $table->decimal('line_total', 16, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_project_quote_items');
    }
};
