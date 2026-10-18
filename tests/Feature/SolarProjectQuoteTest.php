<?php

use App\Models\Appliance;
use App\Models\ElectricalEquipment;
use App\Models\InstallationMaterial;
use App\Models\SolarProject;
use App\Models\SolarProjectQuote;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeQuoteScenario(object $test): array
{
    [$project, $payload] = makeProtectionScenario();
    $panel = ElectricalEquipment::query()->where('name', 'Panel de prueba')->firstOrFail();
    $panel->update(['price' => 1000]);
    $test->put(route('admin.solar-projects.system-design.update', $project), $payload)->assertRedirect();

    return [$project, $panel];
}

test('the quote page lists the design equipment with its quantities and reports categories without catalog items', function () {
    [$project] = makeQuoteScenario($this);

    $this->get(route('admin.solar-projects.quote.show', $project))
        ->assertOk()
        ->assertSee('Panel de prueba')
        ->assertSee('Inversor MPPT limitado')
        ->assertSee('Batería de prueba')
        ->assertSee('Agregar ítem')
        ->assertSee('Guardar cotización')
        ->assertSee('Nombre de la cotización')
        ->assertSee('Mano de obra')
        ->assertSee('DPS');
});

test('the quote page asks for the design first when the project has none', function () {
    $project = SolarProject::factory()->create();

    $this->get(route('admin.solar-projects.quote.show', $project))
        ->assertOk()
        ->assertSee('Calcula primero el');
});

test('saving a quote applies quantity adjustments, tax and discount and keeps the history', function () {
    [$project, $panel] = makeQuoteScenario($this);
    $screw = InstallationMaterial::factory()->create(['name' => 'Tornillos', 'price' => 500, 'category' => 'otro']);

    $response = $this->post(route('admin.solar-projects.quote.store', $project), [
        'name' => 'Cotización residencial',
        'description' => 'Incluye instalación',
        'labor' => ['unit_price' => 3000, 'tax_percent' => 19, 'discount_percent' => 0],
        'lines' => [
            0 => ['item' => 'equipment:'.$panel->id, 'line_key' => 'panel', 'adjustment' => 1, 'tax_percent' => 19, 'discount_percent' => 10],
            1 => ['item' => 'material:'.$screw->id, 'line_key' => '', 'adjustment' => 4, 'tax_percent' => 0, 'discount_percent' => 0],
        ],
    ]);

    $quote = SolarProjectQuote::query()->with('items')->firstOrFail();
    $response->assertRedirect(route('admin.solar-projects.quotes.show', [$project, $quote->id]));

    expect($quote->name)->toBe('Cotización residencial');
    expect($quote->description)->toBe('Incluye instalación');
    expect($quote->items)->toHaveCount(3);
    expect($quote->items[0]->base_quantity)->toBe(9);
    expect($quote->items[0]->quantity)->toBe(10);
    expect((float) $quote->items[0]->line_total)->toBe(10710.0);
    expect($quote->items[1]->base_quantity)->toBe(0);
    expect((float) $quote->items[1]->line_total)->toBe(2000.0);
    expect($quote->items[2]->is_labor)->toBeTrue();
    expect($quote->items[2]->name)->toBe('Mano de obra');
    expect($quote->items[2]->quantity)->toBe(1);
    expect((float) $quote->items[2]->line_total)->toBe(3570.0);
    expect((float) $quote->subtotal)->toBe(15000.0);
    expect((float) $quote->tax_total)->toBe(2470.0);
    expect((float) $quote->discount_total)->toBe(1190.0);
    expect((float) $quote->total)->toBe(16280.0);

    $this->get(route('admin.solar-projects.quotes.show', [$project, $quote->id]))->assertOk()->assertSee('Tornillos')->assertSee('Mano de obra')->assertSee('Incluye instalación');
    $this->post(route('admin.solar-projects.quote.store', $project), [
        'name' => 'Segunda',
        'labor' => ['unit_price' => 0, 'tax_percent' => 0, 'discount_percent' => 0],
        'lines' => [0 => ['item' => 'material:'.$screw->id, 'line_key' => '', 'adjustment' => 1, 'tax_percent' => 19, 'discount_percent' => 0]],
    ])->assertRedirect();
    $this->get(route('admin.solar-projects.quote.show', $project))->assertSee('N.° '.$quote->id)->assertSee('Cotización residencial');
    $this->assertDatabaseCount('solar_project_quotes', 2);
});

test('a quote cannot be saved with a negative final quantity, a missing name or an invalid labor value', function () {
    [$project, $panel] = makeQuoteScenario($this);
    $labor = ['unit_price' => 0, 'tax_percent' => 0, 'discount_percent' => 0];

    $this->post(route('admin.solar-projects.quote.store', $project), ['name' => 'X', 'labor' => $labor, 'lines' => [
        0 => ['item' => 'equipment:'.$panel->id, 'line_key' => 'panel', 'adjustment' => -20, 'tax_percent' => 0, 'discount_percent' => 0],
    ]])->assertSessionHasErrors('lines.0.adjustment');

    $this->post(route('admin.solar-projects.quote.store', $project), ['labor' => $labor, 'lines' => []])->assertSessionHasErrors('name');
    $this->post(route('admin.solar-projects.quote.store', $project), ['name' => 'X', 'labor' => ['unit_price' => -5, 'tax_percent' => 0, 'discount_percent' => 0]])->assertSessionHasErrors('labor.unit_price');
    $this->post(route('admin.solar-projects.quote.store', $project), ['name' => 'X', 'labor' => $labor, 'lines' => [
        0 => ['item' => '', 'line_key' => '', 'adjustment' => 1, 'tax_percent' => 0, 'discount_percent' => 0],
    ]])->assertSessionHasErrors('lines.0.item');

    $this->assertDatabaseCount('solar_project_quotes', 0);
});

test('a quote with only labor is valid and labor quantity cannot be changed from the request', function () {
    [$project] = makeQuoteScenario($this);

    $this->post(route('admin.solar-projects.quote.store', $project), [
        'name' => 'Solo mano de obra',
        'labor' => ['unit_price' => 100000, 'tax_percent' => 0, 'discount_percent' => 50, 'quantity' => 9],
    ])->assertRedirect();

    $item = SolarProjectQuote::query()->firstOrFail()->items()->firstOrFail();
    expect($item->quantity)->toBe(1);
    expect((float) $item->line_total)->toBe(50000.0);
});

test('a saved quote freezes loads and design, so the sheet does not change when the project does', function () {
    [$project, $payload] = makeProtectionScenario();
    $nevera = Appliance::factory()->create(['name' => 'Nevera de prueba', 'energy_consumption_wh' => 100]);
    $horno = Appliance::factory()->create(['name' => 'Horno de prueba', 'energy_consumption_wh' => 900]);
    $load = fn ($appliance) => ['appliance_id' => $appliance->id, 'quantity_total' => 2, 'quantity_night' => 1, 'usage_hours_total' => 5, 'usage_hours_night' => 2];

    $this->post(route('admin.solar-projects.loads.store', $project), $load($nevera))->assertRedirect();
    $this->put(route('admin.solar-projects.system-design.update', $project), $payload)->assertRedirect();
    $this->post(route('admin.solar-projects.quote.store', $project), [
        'name' => 'Cotización congelada',
        'labor' => ['unit_price' => 1000, 'tax_percent' => 0, 'discount_percent' => 0],
    ])->assertRedirect();

    $this->post(route('admin.solar-projects.loads.store', $project), $load($horno))->assertRedirect();
    $quote = SolarProjectQuote::query()->with('items')->firstOrFail();

    expect($project->fresh()->total_consumption_wh_per_day)->not->toBe('1000.00');
    expect($quote->snapshot['loads'])->toHaveCount(1);
    expect($quote->snapshot['loads'][0]['name'])->toBe('Nevera de prueba');
    expect($quote->snapshot['consumption']['total_wh'])->toEqual(1000);
    expect($quote->snapshot['design']['panel']['name'])->toBe('Panel de prueba');
    expect($quote->snapshot['design']['panel_count'])->toBe(2);

    $html = view('admin.solar-projects.quote.sheet', ['quote' => $quote, 'snapshot' => $quote->snapshot])->render();
    expect($html)->toContain('Nevera de prueba')->toContain('Cotización congelada')->not->toContain('Horno de prueba');

    $response = $this->get(route('admin.solar-projects.quotes.sheet', [$project, $quote->id]));
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('a quote without a frozen copy has no sheet', function () {
    $project = SolarProject::factory()->create();
    $quote = $project->quotes()->create(['name' => 'Antigua', 'subtotal' => 0, 'tax_total' => 0, 'discount_total' => 0, 'total' => 0]);

    $this->get(route('admin.solar-projects.quotes.sheet', [$project, $quote->id]))->assertNotFound();
});
