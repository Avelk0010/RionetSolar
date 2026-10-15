<?php

use App\Models\Appliance;
use App\Models\ElectricalEquipment;
use App\Models\ElectricalEquipmentCategory;
use Database\Seeders\ElectricalEquipmentCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validInverterData(int $categoryId): array
{
    return [
        'electrical_equipment_category_id' => $categoryId,
        'name' => 'Inversor de prueba',
        'price' => '1250000.00',
        'mppt_count' => 2,
        'mppt_max_voltage_v' => 500,
        'mppt_max_current_a' => 15,
        'mppt_max_power_w' => 4000,
        'battery_charge_voltage_v' => 48,
        'ac_input_voltage_v' => 220,
        'ac_output_voltage_v' => 110,
        'max_output_current_a' => 30,
        'max_output_power_w' => 4000,
    ];
}

test('creates an appliance with a supported voltage and positive hourly energy', function () {
    $response = $this->post(route('admin.appliances.store'), [
        'name' => 'Nevera de prueba', 'voltage' => 110, 'energy_consumption_wh' => 180.5,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('appliances', ['name' => 'Nevera de prueba', 'voltage' => 110, 'energy_consumption_wh' => 180.5]);
});

test('rejects appliance voltages outside 110 and 220 volts', function () {
    $response = $this->post(route('admin.appliances.store'), [
        'name' => 'Carga inválida', 'voltage' => 120, 'energy_consumption_wh' => 100,
    ]);

    $response->assertSessionHasErrors('voltage');
    $this->assertDatabaseCount('appliances', 0);
});

test('rejects appliance power that is not positive', function (int|float $power) {
    $response = $this->post(route('admin.appliances.store'), [
        'name' => 'Carga inválida', 'voltage' => 220, 'energy_consumption_wh' => $power,
    ]);

    $response->assertSessionHasErrors('energy_consumption_wh');
    $this->assertDatabaseCount('appliances', 0);
})->with([
    'zero watts' => [0],
    'negative watts' => [-5],
]);

test('creates equipment and its matching technical sheet for every supported category', function (string $slug, string $table, array $technicalData) {
    $this->seed(ElectricalEquipmentCategorySeeder::class);
    $category = ElectricalEquipmentCategory::query()->where('slug', $slug)->firstOrFail();
    $response = $this->post(route('admin.electrical-equipment.store'), array_merge([
        'electrical_equipment_category_id' => $category->id,
        'name' => 'Equipo '.$slug,
        'price' => '250000.00',
    ], $technicalData));

    $response->assertRedirect();
    $this->assertDatabaseCount('electrical_equipment', 1);
    $this->assertDatabaseCount($table, 1);

    if ($slug === 'paneles-solares') {
        $this->assertDatabaseHas($table, [
            'max_power_voltage_v' => $technicalData['max_power_voltage_v'],
            'max_power_current_a' => $technicalData['max_power_current_a'],
        ]);
    }
})->with([
    'inverter' => ['inversores', 'inverter_specs', [
        'mppt_count' => 2, 'mppt_max_voltage_v' => 500, 'mppt_max_current_a' => 15, 'mppt_max_power_w' => 4000,
        'battery_charge_voltage_v' => 48, 'ac_input_voltage_v' => 220, 'ac_output_voltage_v' => 110,
        'max_output_current_a' => 30, 'max_output_power_w' => 4000,
    ]],
    'solar panel' => ['paneles-solares', 'solar_panel_specs', [
        'max_power_w' => 550, 'max_power_voltage_v' => 41.8, 'max_power_current_a' => 13.16,
        'open_circuit_voltage_v' => 49.8, 'short_circuit_current_a' => 14,
        'max_system_voltage_v' => 1500, 'length_mm' => 2278, 'width_mm' => 1134, 'height_mm' => 35,
    ]],
    'battery' => ['baterias', 'battery_specs', ['capacity_ah' => 100, 'voltage_v' => 48]],
    'DC breaker' => ['breakers-dc', 'dc_breaker_specs', [
        'min_operating_voltage_v' => 12, 'max_operating_voltage_v' => 600, 'rated_current_a' => 32,
    ]],
    'AC breaker' => ['breakers-ac', 'ac_breaker_specs', ['operating_voltage_v' => 220, 'rated_current_a' => 32]],
    'solar cable' => ['cable-solar', 'solar_cable_specs', [
        'awg_gauge' => '4/0', 'diameter_mm' => 11.7, 'nominal_voltage_dc_v' => 1000,
    ]],
]);

test('rejects an incomplete technical sheet without creating its equipment', function () {
    $this->seed(ElectricalEquipmentCategorySeeder::class);
    $category = ElectricalEquipmentCategory::query()->where('slug', 'inversores')->firstOrFail();
    $response = $this->post(route('admin.electrical-equipment.store'), [
        'electrical_equipment_category_id' => $category->id, 'name' => 'Inversor incompleto', 'price' => 1000,
        'mppt_count' => 2,
    ]);

    $response->assertSessionHasErrors(['mppt_max_voltage_v', 'ac_input_voltage_v']);
    $this->assertDatabaseCount('electrical_equipment', 0);
});

test('rejects a solar panel without a positive maximum power voltage', function (int|float|null $voltage) {
    $this->seed(ElectricalEquipmentCategorySeeder::class);
    $category = ElectricalEquipmentCategory::query()->where('slug', 'paneles-solares')->firstOrFail();
    $technicalData = [
        'electrical_equipment_category_id' => $category->id,
        'name' => 'Panel sin Vmp válido',
        'price' => 250000,
        'max_power_w' => 550,
        'open_circuit_voltage_v' => 49.8,
        'short_circuit_current_a' => 14,
        'max_system_voltage_v' => 1500,
        'length_mm' => 2278,
        'width_mm' => 1134,
        'height_mm' => 35,
    ];

    if ($voltage !== null) {
        $technicalData['max_power_voltage_v'] = $voltage;
    }

    $response = $this->post(route('admin.electrical-equipment.store'), $technicalData);

    $response->assertSessionHasErrors('max_power_voltage_v');
    $this->assertDatabaseCount('electrical_equipment', 0);
})->with([
    'missing voltage' => [null],
    'zero voltage' => [0],
]);

test('rejects a solar panel without a positive maximum power current', function (int|float|null $current) {
    $this->seed(ElectricalEquipmentCategorySeeder::class);
    $category = ElectricalEquipmentCategory::query()->where('slug', 'paneles-solares')->firstOrFail();
    $technicalData = [
        'electrical_equipment_category_id' => $category->id,
        'name' => 'Panel sin Imp válido',
        'price' => 250000,
        'max_power_w' => 550,
        'max_power_voltage_v' => 41.8,
        'open_circuit_voltage_v' => 49.8,
        'short_circuit_current_a' => 14,
        'max_system_voltage_v' => 1500,
        'length_mm' => 2278,
        'width_mm' => 1134,
        'height_mm' => 35,
    ];

    if ($current !== null) {
        $technicalData['max_power_current_a'] = $current;
    }

    $response = $this->post(route('admin.electrical-equipment.store'), $technicalData);

    $response->assertSessionHasErrors('max_power_current_a');
    $this->assertDatabaseCount('electrical_equipment', 0);
})->with([
    'missing current' => [null],
    'zero current' => [0],
]);

test('rejects technical attributes that do not match the selected category', function () {
    $this->seed(ElectricalEquipmentCategorySeeder::class);
    $category = ElectricalEquipmentCategory::query()->where('slug', 'inversores')->firstOrFail();
    $response = $this->post(route('admin.electrical-equipment.store'), array_merge(
        validInverterData($category->id), ['capacity_ah' => 100],
    ));

    $response->assertSessionHasErrors('capacity_ah');
    $this->assertDatabaseCount('electrical_equipment', 0);
});

test('updates an equipment record and its technical sheet together', function () {
    $this->seed(ElectricalEquipmentCategorySeeder::class);
    $category = ElectricalEquipmentCategory::query()->where('slug', 'inversores')->firstOrFail();
    $this->post(route('admin.electrical-equipment.store'), validInverterData($category->id));
    $equipment = ElectricalEquipment::query()->firstOrFail();
    $updated = validInverterData($category->id);
    $updated['name'] = 'Inversor actualizado';
    $updated['mppt_max_power_w'] = 5000;

    $response = $this->put(route('admin.electrical-equipment.update', $equipment), $updated);

    $response->assertRedirect(route('admin.electrical-equipment.show', $equipment));
    $this->assertDatabaseHas('electrical_equipment', ['id' => $equipment->id, 'name' => 'Inversor actualizado']);
    $this->assertDatabaseHas('inverter_specs', ['electrical_equipment_id' => $equipment->id, 'mppt_max_power_w' => 5000]);
});

test('creates materials and rejects negative prices', function () {
    $response = $this->post(route('admin.installation-materials.store'), [
        'name' => 'Riel de prueba', 'price' => '12500.50', 'unit' => 'metro',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('installation_materials', ['name' => 'Riel de prueba', 'price' => '12500.50']);
    $response = $this->post(route('admin.installation-materials.store'), [
        'name' => 'Material inválido', 'price' => -1, 'unit' => 'unidad',
    ]);

    $response->assertSessionHasErrors('price');
    $this->assertDatabaseCount('installation_materials', 1);
});

test('category seeding is idempotent and model relationships are available', function () {
    $this->seed(ElectricalEquipmentCategorySeeder::class);
    $this->seed(ElectricalEquipmentCategorySeeder::class);
    $category = ElectricalEquipmentCategory::query()->where('slug', 'inversores')->firstOrFail();
    $this->post(route('admin.electrical-equipment.store'), validInverterData($category->id));
    $equipment = ElectricalEquipment::query()->with('category', 'inverterSpec')->firstOrFail();

    $this->assertDatabaseCount('electrical_equipment_categories', 6);
    expect($equipment->category->slug)->toBe('inversores');
    expect($equipment->inverterSpec->equipment->is($equipment))->toBeTrue();
    expect($equipment->category->equipment->first()->is($equipment))->toBeTrue();
});

test('inactive appliances remain available in catalog filters', function () {
    $appliance = Appliance::factory()->create(['is_active' => false]);

    $this->get(route('admin.appliances.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee($appliance->name);

    expect(Appliance::active()->count())->toBe(0);
});
