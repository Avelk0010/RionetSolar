<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solar_project_quotes', function (Blueprint $table) {
            $table->longText('snapshot')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('solar_project_quotes', function (Blueprint $table) {
            $table->dropColumn('snapshot');
        });
    }
};
