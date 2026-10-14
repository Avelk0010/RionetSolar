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
        if (! Schema::hasTable('electrical_equipment')) {
            Schema::create('electrical_equipment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('electrical_equipment_category_id')
                    ->constrained('electrical_equipment_categories')
                    ->restrictOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 14, 2);
                $table->string('brand')->nullable();
                $table->string('model')->nullable();
                $table->string('sku')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('electrical_equipment', function (Blueprint $table) {
            $table->unique('sku', 'electrical_equipment_sku_unique');
            $table->index(['electrical_equipment_category_id', 'is_active'], 'equipment_category_active_idx');
            $table->index('name', 'electrical_equipment_name_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electrical_equipment');
    }
};
