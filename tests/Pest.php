<?php

use App\Models\ElectricalEquipment;
use App\Models\ElectricalEquipmentCategory;
use App\Models\SolarProject;
use Database\Seeders\ElectricalEquipmentCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function makeDesignEquipment(string $slug, string $name, array $specifications): ElectricalEquipment
{
    $category = ElectricalEquipmentCategory::query()->where('slug', $slug)->firstOrFail();
    $equipment = ElectricalEquipment::factory()->create([
        'electrical_equipment_category_id' => $category->id,
        'name' => $name,
    ]);
    $relationship = match ($slug) {
        'paneles-solares' => 'solarPanelSpec',
        'inversores' => 'inverterSpec',
        'baterias' => 'batterySpec',
        'breakers-dc' => 'dcBreakerSpec',
        'breakers-ac' => 'acBreakerSpec',
    };
    $equipment->{$relationship}()->create($specifications);

    return $equipment;
}

/** Proyecto con consumo cargado y los equipos de un dimensionamiento válido (3 inversores por límite MPPT). */
function makeProtectionScenario(): array
{
    app(ElectricalEquipmentCategorySeeder::class)->run();
    $panel = makeDesignEquipment('paneles-solares', 'Panel de prueba', [
        'max_power_w' => 500, 'max_power_voltage_v' => 41, 'max_power_current_a' => 12.2,
        'open_circuit_voltage_v' => 49, 'short_circuit_current_a' => 13,
        'max_system_voltage_v' => 1500, 'length_mm' => 2000, 'width_mm' => 1000, 'height_mm' => 35,
    ]);
    $inverter = makeDesignEquipment('inversores', 'Inversor MPPT limitado', [
        'mppt_count' => 1, 'mppt_max_voltage_v' => 500, 'mppt_max_current_a' => 15,
        'mppt_max_power_w' => 2000, 'battery_charge_voltage_v' => 48,
        'ac_input_voltage_v' => 220, 'ac_output_voltage_v' => 220,
        'max_output_current_a' => 25, 'max_output_power_w' => 3000,
    ]);
    $battery = makeDesignEquipment('baterias', 'Batería de prueba', [
        'capacity_ah' => 100, 'voltage_v' => 48, 'nominal_power_w' => null,
    ]);
    $project = SolarProject::factory()->create([
        'solar_hours_per_day' => 1,
        'total_consumption_wh_per_day' => 4500,
        'night_consumption_wh_per_day' => 2000,
        'maximum_hourly_power_w' => 3500,
    ]);

    return [$project, [
        'solar_panel_equipment_id' => $panel->id,
        'inverter_equipment_id' => $inverter->id,
        'battery_equipment_id' => $battery->id,
        'total_oversizing_percent' => 0,
        'night_oversizing_percent' => 0,
        'max_battery_discharge_percent' => 80,
    ]];
}
