<?php

use App\Models\Appliance;
use App\Models\ElectricalEquipment;
use App\Models\ElectricalEquipmentCategory;
use App\Models\SolarProject;
use App\Models\SolarProjectAppliance;
use App\Models\SolarProjectSystemDesign;
use Database\Seeders\ElectricalEquipmentCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
test('creates a solar project and opens its project workspace', function () {
    $response = $this->post(route('admin.solar-projects.store'), [
        'name' => 'Sistema residencial',
        'description' => 'Casa de prueba',
        'solar_hours_per_day' => 5.5,
        'latitude' => 4.711,
        'longitude' => -74.0721,
    ]);

    $project = SolarProject::query()->firstOrFail();

    $response->assertRedirect(route('admin.solar-projects.show', $project));
    $this->assertDatabaseHas('solar_projects', [
        'id' => $project->id,
        'name' => 'Sistema residencial',
        'solar_string_count' => null,
    ]);
    $this->get(route('admin.solar-projects.show', $project))
        ->assertOk()
        ->assertSee('Cargas eléctricas')
        ->assertSee('Dimensionamiento');
});

test('requires coordinates as a pair', function () {
    $response = $this->post(route('admin.solar-projects.store'), [
        'name' => 'Proyecto incompleto',
        'solar_hours_per_day' => 5,
        'latitude' => 4.7,
    ]);

    $response->assertSessionHasErrors('latitude');
    $this->assertDatabaseCount('solar_projects', 0);
});

test('new projects start with empty calculated totals and no strings', function () {
    $project = SolarProject::factory()->create();

    expect((float) $project->total_consumption_wh_per_day)->toBe(0.0);
    expect((float) $project->night_consumption_wh_per_day)->toBe(0.0);
    expect($project->maximum_hourly_power_w)->toBeNull();
    expect($project->solar_string_count)->toBeNull();
});

test('project appliance items belong to their project and master appliance', function () {
    $project = SolarProject::factory()->create();
    $appliance = Appliance::factory()->create();
    $item = SolarProjectAppliance::query()->create([
        'solar_project_id' => $project->id,
        'appliance_id' => $appliance->id,
        'appliance_energy_consumption_wh' => 120,
        'quantity_total' => 2,
        'quantity_night' => 1,
        'usage_hours_total' => 4,
        'usage_hours_night' => 2,
        'daily_energy_wh' => 960,
        'night_energy_wh' => 240,
        'peak_power_w' => 240,
    ]);

    expect($project->applianceItems()->first()->is($item))->toBeTrue();
    expect($item->project->is($project))->toBeTrue();
    expect($item->appliance->is($appliance))->toBeTrue();
});

test('adding and removing an appliance recalculates project energy and peak power', function () {
    $project = SolarProject::factory()->create();
    $appliance = Appliance::factory()->create(['energy_consumption_wh' => 120]);
    $data = [
        'appliance_id' => $appliance->id,
        'quantity_total' => 2,
        'quantity_night' => 1,
        'usage_hours_total' => 4,
        'usage_hours_night' => 2,
    ];

    $response = $this->post(route('admin.solar-projects.loads.store', $project), $data);
    $item = SolarProjectAppliance::query()->firstOrFail();

    $response->assertRedirect(route('admin.solar-projects.loads.index', $project));
    $this->assertDatabaseHas('solar_project_appliances', [
        'id' => $item->id,
        'daily_energy_wh' => 960,
        'night_energy_wh' => 240,
        'peak_power_w' => 240,
    ]);

    $project->refresh();
    expect((float) $project->total_consumption_wh_per_day)->toBe(960.0);
    expect((float) $project->night_consumption_wh_per_day)->toBe(240.0);
    expect((float) $project->maximum_hourly_power_w)->toBe(240.0);

    $this->delete(route('admin.solar-projects.loads.destroy', [$project, $item]))->assertRedirect();
    $project->refresh();
    expect((float) $project->total_consumption_wh_per_day)->toBe(0.0);
    expect((float) $project->night_consumption_wh_per_day)->toBe(0.0);
    expect($project->maximum_hourly_power_w)->toBeNull();
});

test('solar design rounds panels evenly across inverter strings and uses battery watt-hours', function () {
    $this->seed(ElectricalEquipmentCategorySeeder::class);
    $panel = makeDesignEquipment('paneles-solares', 'Panel de prueba', [
        'max_power_w' => 500, 'max_power_voltage_v' => 41, 'max_power_current_a' => 12.2,
        'open_circuit_voltage_v' => 49, 'short_circuit_current_a' => 13,
        'max_system_voltage_v' => 1500, 'length_mm' => 2000, 'width_mm' => 1000, 'height_mm' => 35,
    ]);
    $inverter = makeDesignEquipment('inversores', 'Inversor de prueba', [
        'mppt_count' => 1, 'mppt_max_voltage_v' => 500, 'mppt_max_current_a' => 15,
        'mppt_max_power_w' => 3000, 'battery_charge_voltage_v' => 48,
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

    $response = $this->put(route('admin.solar-projects.system-design.update', $project), [
        'solar_panel_equipment_id' => $panel->id,
        'inverter_equipment_id' => $inverter->id,
        'battery_equipment_id' => $battery->id,
        'total_oversizing_percent' => 0,
        'night_oversizing_percent' => 0,
        'max_battery_discharge_percent' => 80,
    ]);
    $design = SolarProjectSystemDesign::query()->where('solar_project_id', $project->id)->firstOrFail();

    $response->assertRedirect(route('admin.solar-projects.system-design.show', $project));
    expect($design->panel_count)->toBe(10);
    expect($design->inverter_count)->toBe(2);
    expect($design->battery_count)->toBe(1);
    expect($design->panels_per_string)->toBe(5);
    expect((float) $design->array_power_w)->toBe(5000.0);
    expect($project->fresh()->solar_string_count)->toBe(2);
    $this->get(route('admin.solar-projects.system-design.show', $project))
        ->assertOk()
        ->assertSee('String 1')
        ->assertSee('5 paneles');

    $appliance = Appliance::factory()->create(['energy_consumption_wh' => 100]);
    $this->post(route('admin.solar-projects.loads.store', $project), [
        'appliance_id' => $appliance->id,
        'quantity_total' => 1,
        'quantity_night' => 1,
        'usage_hours_total' => 1,
        'usage_hours_night' => 1,
    ])->assertRedirect();

    expect($project->fresh()->solar_string_count)->toBeNull();
    expect($project->fresh()->systemDesign->panel_count)->toBeNull();
});

test('solar design rejects a battery voltage incompatible with the inverter', function () {
    $this->seed(ElectricalEquipmentCategorySeeder::class);
    $panel = makeDesignEquipment('paneles-solares', 'Panel de prueba', [
        'max_power_w' => 500, 'max_power_voltage_v' => 41, 'max_power_current_a' => 12.2,
        'open_circuit_voltage_v' => 49, 'short_circuit_current_a' => 13,
        'max_system_voltage_v' => 1500, 'length_mm' => 2000, 'width_mm' => 1000, 'height_mm' => 35,
    ]);
    $inverter = makeDesignEquipment('inversores', 'Inversor de prueba', [
        'mppt_count' => 1, 'mppt_max_voltage_v' => 500, 'mppt_max_current_a' => 15,
        'mppt_max_power_w' => 3000, 'battery_charge_voltage_v' => 48,
        'ac_input_voltage_v' => 220, 'ac_output_voltage_v' => 220,
        'max_output_current_a' => 25, 'max_output_power_w' => 3000,
    ]);
    $battery = makeDesignEquipment('baterias', 'Batería incompatible', [
        'capacity_ah' => 100, 'voltage_v' => 24, 'nominal_power_w' => null,
    ]);
    $project = SolarProject::factory()->create([
        'total_consumption_wh_per_day' => 1000,
        'maximum_hourly_power_w' => 500,
    ]);

    $response = $this->put(route('admin.solar-projects.system-design.update', $project), [
        'solar_panel_equipment_id' => $panel->id,
        'inverter_equipment_id' => $inverter->id,
        'battery_equipment_id' => $battery->id,
        'total_oversizing_percent' => 0,
        'night_oversizing_percent' => 0,
        'max_battery_discharge_percent' => 80,
    ]);

    $response->assertSessionHasErrors('battery_equipment_id');
    $this->assertDatabaseCount('solar_project_system_designs', 0);
});

test('solar design rejects a string whose panel power exceeds the inverter MPPT limit', function () {
    $this->seed(ElectricalEquipmentCategorySeeder::class);
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

    $response = $this->put(route('admin.solar-projects.system-design.update', $project), [
        'solar_panel_equipment_id' => $panel->id,
        'inverter_equipment_id' => $inverter->id,
        'battery_equipment_id' => $battery->id,
        'total_oversizing_percent' => 0,
        'night_oversizing_percent' => 0,
        'max_battery_discharge_percent' => 80,
    ]);

    $response->assertSessionHasErrors('solar_panel_equipment_id');
    $this->assertDatabaseCount('solar_project_system_designs', 0);
    expect(SolarProject::query()->findOrFail($project->id)->solar_string_count)->toBeNull();
});

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
    };
    $equipment->{$relationship}()->create($specifications);

    return $equipment;
}
