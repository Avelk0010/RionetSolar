<?php

use App\Http\Controllers\Admin\ApplianceController;
use App\Http\Controllers\Admin\ElectricalEquipmentController;
use App\Http\Controllers\Admin\InstallationMaterialController;
use App\Models\Appliance;
use App\Models\ElectricalEquipment;
use App\Models\InstallationMaterial;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard', [
        'applianceCount' => Appliance::query()->count(),
        'equipmentCount' => ElectricalEquipment::query()->count(),
        'materialCount' => InstallationMaterial::query()->count(),
    ]);
})->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::resource('appliances', ApplianceController::class);
    Route::resource('electrical-equipment', ElectricalEquipmentController::class)
        ->parameters(['electrical-equipment' => 'equipment']);
    Route::resource('installation-materials', InstallationMaterialController::class)
        ->parameters(['installation-materials' => 'material']);
});
