<?php

use App\Http\Controllers\Admin\ApplianceController;
use App\Http\Controllers\Admin\ElectricalEquipmentController;
use App\Http\Controllers\Admin\InstallationMaterialController;
use App\Http\Controllers\Admin\SolarProjectApplianceController;
use App\Http\Controllers\Admin\SolarProjectController;
use App\Http\Controllers\Admin\SolarProjectQuoteController;
use App\Http\Controllers\Admin\SolarProjectSystemDesignController;
use App\Models\Appliance;
use App\Models\ElectricalEquipment;
use App\Models\InstallationMaterial;
use App\Models\SolarProject;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard', [
        'applianceCount' => Appliance::query()->count(),
        'equipmentCount' => ElectricalEquipment::query()->count(),
        'materialCount' => InstallationMaterial::query()->count(),
        'projectCount' => SolarProject::query()->count(),
    ]);
})->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::resource('appliances', ApplianceController::class);
    Route::resource('electrical-equipment', ElectricalEquipmentController::class)
        ->parameters(['electrical-equipment' => 'equipment']);
    Route::delete('installation-materials/{material}/permanent', [InstallationMaterialController::class, 'forceDestroy'])
        ->name('installation-materials.force-destroy');
    Route::resource('installation-materials', InstallationMaterialController::class)
        ->parameters(['installation-materials' => 'material']);
    Route::resource('solar-projects', SolarProjectController::class)
        ->parameters(['solar-projects' => 'solarProject']);

    Route::get('solar-projects/{solarProject}/loads', [SolarProjectApplianceController::class, 'index'])
        ->name('solar-projects.loads.index');
    Route::post('solar-projects/{solarProject}/loads', [SolarProjectApplianceController::class, 'store'])
        ->name('solar-projects.loads.store');
    Route::delete('solar-projects/{solarProject}/loads/{item}', [SolarProjectApplianceController::class, 'destroy'])
        ->name('solar-projects.loads.destroy');
    Route::get('solar-projects/{solarProject}/system-design', [SolarProjectSystemDesignController::class, 'show'])
        ->name('solar-projects.system-design.show');
    Route::put('solar-projects/{solarProject}/system-design', [SolarProjectSystemDesignController::class, 'update'])
        ->name('solar-projects.system-design.update');
    Route::put('solar-projects/{solarProject}/system-design/string-rows', [SolarProjectSystemDesignController::class, 'updateStringRows'])
        ->name('solar-projects.system-design.string-rows.update');
    Route::get('solar-projects/{solarProject}/quote', [SolarProjectQuoteController::class, 'show'])
        ->name('solar-projects.quote.show');
    Route::post('solar-projects/{solarProject}/quote', [SolarProjectQuoteController::class, 'store'])
        ->name('solar-projects.quote.store');
    Route::get('solar-projects/{solarProject}/quotes/{quote}', [SolarProjectQuoteController::class, 'showSaved'])
        ->whereNumber('quote')
        ->name('solar-projects.quotes.show');
    Route::get('solar-projects/{solarProject}/quotes/{quote}/sheet', [SolarProjectQuoteController::class, 'sheet'])
        ->whereNumber('quote')
        ->name('solar-projects.quotes.sheet');
});
