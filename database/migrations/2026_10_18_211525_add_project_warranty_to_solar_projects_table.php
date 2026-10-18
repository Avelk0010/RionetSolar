<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('solar_projects', function (Blueprint $table) {
            $table->text('project_warranty')->nullable()->after('description');
        });

        $defaultWarranty = <<<'WARRANTY'
Garantía del proyecto directamente con la empresa instaladora RIONET ISP SAS

- Paneles Solares: 10 años
- Inversores: 5 años
- Baterías: 5 años

Nota: La garantía no cubre daños por golpes contundentes, equipos visiblemente maltratados y daños por mal manejo o manipulación de equipos instalados por parte de terceros ajenos a RIONET ISP SAS
WARRANTY;

        DB::table('solar_projects')
            ->whereNull('project_warranty')
            ->update(['project_warranty' => $defaultWarranty]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_projects', function (Blueprint $table) {
            $table->dropColumn('project_warranty');
        });
    }
};
