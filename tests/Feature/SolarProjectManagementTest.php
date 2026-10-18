<?php

use App\Models\Appliance;
use App\Models\InstallationMaterial;
use App\Models\SolarProject;
use App\Models\SolarProjectAppliance;
use App\Models\SolarProjectSystemDesign;
use App\Services\SolarProjectMaterialCalculator;
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
    $diagramResponse = $this->get(route('admin.solar-projects.system-design.show', $project));
    $diagramResponse->assertOk()
        ->assertSee('String 1')
        ->assertSee('5 paneles')
        ->assertSee('Diagrama del sistema')
        ->assertSee('Campo fotovoltaico')
        ->assertSee('10 paneles')
        ->assertSee('Protecciones DC')
        ->assertSee('2 inversores')
        ->assertSee('1 batería')
        ->assertSee('Breaker AC input')
        ->assertSee('Breaker AC output')
        ->assertSee('Baterías')
        ->assertSee('Cargas AC');

    expect(substr_count($diagramResponse->getContent(), asset('images/diagrama/panel.png')))->toBe(10);
    expect(substr_count($diagramResponse->getContent(), asset('images/diagrama/inversor.png')))->toBe(2);
    expect(substr_count($diagramResponse->getContent(), asset('images/diagrama/bateria.png')))->toBe(1);

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

test('solar design adds inverters when a string would exceed the MPPT limit', function () {
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

    $response->assertRedirect(route('admin.solar-projects.system-design.show', $project));
    $design = SolarProjectSystemDesign::query()->where('solar_project_id', $project->id)->firstOrFail();
    expect($design->inverter_count)->toBe(3);
    expect($design->panels_per_string)->toBe(3);
    expect($design->panel_count)->toBe(9);
    expect($project->fresh()->solar_string_count)->toBe(3);
    $this->get(route('admin.solar-projects.system-design.show', $project))
        ->assertOk()
        ->assertSee('Inversor MPPT limitado: se necesitan 3 inversores')
        ->assertSee('límite MPPT')
        ->assertSee('Panel de prueba: se necesitan 9 paneles')
        ->assertSee('Batería de prueba: se necesitan 1 baterías');
});

test('material calculator reuses leftover rail pieces only for partial panel segments', function (array $layout, array $expected) {
    $materials = app(SolarProjectMaterialCalculator::class)->calculate($layout);

    expect($materials)->toMatchArray($expected);
})->with([
    'nine panels in one row' => [[1 => [9]], ['rails' => 5, 'rail_leftover_quarters' => 2, 'rail_unions' => 4, 'ground_connectors' => 2, 'mc4_connectors' => 2, 'end_clamps' => 4, 'mid_clamps' => 16]],
    'eleven panels in rows of 5, 5 and 1' => [[1 => [5, 5, 1]], ['rails' => 6, 'rail_leftover_quarters' => 2, 'rail_unions' => 4, 'ground_connectors' => 6, 'mc4_connectors' => 6, 'end_clamps' => 12, 'mid_clamps' => 16]],
    'two panels use a half rail with no waste' => [[1 => [2]], ['rails' => 1, 'rail_leftover_quarters' => 0, 'rail_unions' => 0, 'mid_clamps' => 2]],
    'four panels use whole rails only' => [[1 => [4]], ['rails' => 2, 'rail_leftover_quarters' => 0, 'rail_unions' => 0, 'mid_clamps' => 6]],
]);

test('string rows are saved by hand, validated against the string size and drive the materials', function () {
    [$project, $payload] = makeProtectionScenario();
    $this->put(route('admin.solar-projects.system-design.update', $project), $payload)->assertRedirect();

    $this->put(route('admin.solar-projects.system-design.string-rows.update', $project), [
        'rows' => [1 => [2, 2], 2 => [3], 3 => [1, 1, 1]],
    ])->assertSessionHasErrors('rows.1');
    $this->assertDatabaseCount('solar_project_string_rows', 0);

    $this->put(route('admin.solar-projects.system-design.string-rows.update', $project), [
        'rows' => [1 => [2, 0], 2 => [3], 3 => [1, 1, 1]],
    ])->assertSessionHasErrors('rows.1.1');

    $this->put(route('admin.solar-projects.system-design.string-rows.update', $project), [
        'rows' => [1 => [2, 1], 2 => [3], 3 => [1, 1, 1]],
    ])->assertRedirect();

    $this->assertDatabaseCount('solar_project_string_rows', 6);
    $this->get(route('admin.solar-projects.system-design.show', $project))
        ->assertOk()
        ->assertSee('Materiales necesarios')
        ->assertSee('Agregar fila')
        ->assertSee('3 paneles en serie');

    $this->put(route('admin.solar-projects.system-design.update', $project), array_merge($payload, ['total_oversizing_percent' => 100]))->assertRedirect();
    $this->assertDatabaseCount('solar_project_string_rows', 0);
});

test('protections show catalog breakers and DPS matching the design requirements', function () {
    [$project, $payload] = makeProtectionScenario();
    makeDesignEquipment('breakers-dc', 'Breaker DC adecuado', ['poles' => 2, 'min_operating_voltage_v' => 100, 'max_operating_voltage_v' => 600, 'rated_current_a' => 16]);
    makeDesignEquipment('breakers-dc', 'Breaker DC unipolar', ['poles' => 1, 'min_operating_voltage_v' => 100, 'max_operating_voltage_v' => 600, 'rated_current_a' => 16]);
    makeDesignEquipment('breakers-dc', 'DPS adecuado', ['poles' => 2, 'min_operating_voltage_v' => 100, 'max_operating_voltage_v' => 600, 'rated_current_a' => 20000]);
    makeDesignEquipment('breakers-ac', 'Breaker AC adecuado', ['poles' => 2, 'operating_voltage_v' => 240, 'rated_current_a' => 27]);
    makeDesignEquipment('breakers-ac', 'Breaker AC lejano', ['poles' => 2, 'operating_voltage_v' => 240, 'rated_current_a' => 40]);

    $this->put(route('admin.solar-projects.system-design.update', $project), $payload)->assertRedirect();

    $this->get(route('admin.solar-projects.system-design.show', $project))
        ->assertOk()
        ->assertSee('Breakers y protecciones')
        ->assertSee('Breaker DC adecuado')
        ->assertSee('DPS adecuado')
        ->assertSee('Breaker AC adecuado')
        ->assertDontSee('Breaker DC unipolar')
        ->assertDontSee('Breaker AC lejano')
        ->assertDontSee('No hay ítems en el catálogo que cumplan con los requerimientos.');
});

test('protections report when the catalog has no matching items', function () {
    [$project, $payload] = makeProtectionScenario();
    makeDesignEquipment('breakers-dc', 'Breaker DC insuficiente', ['poles' => 2, 'min_operating_voltage_v' => 100, 'max_operating_voltage_v' => 100, 'rated_current_a' => 5]);

    $this->put(route('admin.solar-projects.system-design.update', $project), $payload)->assertRedirect();

    $this->get(route('admin.solar-projects.system-design.show', $project))
        ->assertOk()
        ->assertSee('No hay ítems en el catálogo que cumplan con los requerimientos.')
        ->assertDontSee('Breaker DC insuficiente');
});

test('project materials show the active catalog material of each category or a missing-material message', function () {
    [$project, $payload] = makeProtectionScenario();
    $this->put(route('admin.solar-projects.system-design.update', $project), $payload)->assertRedirect();

    $this->get(route('admin.solar-projects.system-design.show', $project))
        ->assertOk()
        ->assertSee('No hay material registrado con esta categoría.');

    InstallationMaterial::factory()->create(['name' => 'Riel Aluminio 4m', 'category' => 'riel-estructura-solar']);
    InstallationMaterial::factory()->create(['name' => 'Mid clamp inactivo', 'category' => 'mid-clamp', 'is_active' => false]);

    $this->get(route('admin.solar-projects.system-design.show', $project))
        ->assertOk()
        ->assertSee('Riel Aluminio 4m')
        ->assertDontSee('Mid clamp inactivo')
        ->assertSee('No hay material registrado con esta categoría.');
});
