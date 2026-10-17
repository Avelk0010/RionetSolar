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
        Schema::table('solar_project_quotes', function (Blueprint $table) {
            $table->string('name')->default('')->after('solar_project_id');
            $table->text('description')->nullable()->after('name');
        });

        Schema::table('solar_project_quote_items', function (Blueprint $table) {
            $table->boolean('is_labor')->default(false)->after('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_project_quote_items', function (Blueprint $table) {
            $table->dropColumn('is_labor');
        });

        Schema::table('solar_project_quotes', function (Blueprint $table) {
            $table->dropColumn(['name', 'description']);
        });
    }
};
