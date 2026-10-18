<?php

use App\Models\Appliance;
use App\Models\ElectricalEquipment;
use App\Models\InstallationMaterial;
use App\Models\SolarProject;
use App\Models\SolarProjectQuote;
use App\Services\SolarProjectQuoteBuilder;
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
        ->assertSee('Escenario de cotización')
        ->assertSee('Diagrama del sistema')
        ->assertSee('Mano de obra')
        ->assertSee('DPS');
});

test('the quote page asks for the design first when the project has none', function () {
    $project = SolarProject::factory()->create();

    $this->get(route('admin.solar-projects.quote.show', $project))
        ->assertOk()
        ->assertSee('Calcula primero el');
});

test('quote folio displays 35 for internal id 5 without changing the route key', function () {
    [$project] = makeQuoteScenario($this);

    foreach (range(1, 5) as $number) {
        $project->quotes()->create([
            'name' => 'Cotización '.$number,
            'snapshot' => $number === 5 ? app(SolarProjectQuoteBuilder::class)->snapshot($project, $project->systemDesign) : null,
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'total' => 0,
        ]);
    }

    $quote = SolarProjectQuote::query()->findOrFail(5);
    expect($quote->getKey())->toBe(5);

    $this->get(route('admin.solar-projects.quote.show', $project))
        ->assertOk()
        ->assertSee('N.° 35');
    $this->get(route('admin.solar-projects.quotes.show', [$project, 5]))
        ->assertOk()
        ->assertSee('N.° 35')
        ->assertSee('Cotización 5');

    $quote = $quote->fresh();
    $sheet = view('admin.solar-projects.quote.sheet', ['quote' => $quote, 'snapshot' => $quote->snapshot])->render();
    expect($sheet)->toContain('<title>Ficha de cotización N.° 35</title>');

    $pdf = $this->get(route('admin.solar-projects.quotes.sheet', [$project, 5]));
    $pdf->assertOk();
    expect($pdf->headers->get('content-disposition'))->toContain('ficha-cotizacion-35.pdf');
});

test('quote preview balances adjusted panels across MPPT-valid strings without changing the saved design', function () {
    [$project] = makeQuoteScenario($this);
    $design = $project->fresh()->systemDesign;
    $lines = [
        ['item' => 'equipment:'.$design->solarPanel->id, 'line_key' => 'panel', 'adjustment' => 5],
        ['item' => 'equipment:'.$design->inverter->id, 'line_key' => 'inverter', 'adjustment' => 0],
        ['item' => 'equipment:'.$design->battery->id, 'line_key' => 'battery', 'adjustment' => 0],
    ];

    $response = $this->postJson(route('admin.solar-projects.quote.preview', $project), ['lines' => $lines]);
    $response->assertOk()
        ->assertJsonPath('requested_panel_count', 14)
        ->assertJsonPath('balanced_panel_count', 16)
        ->assertJsonPath('effective_adjustments.panel', 7)
        ->assertJsonPath('effective_adjustments.inverter', 1);

    expect($response->json('diagram_html'))->toContain('String 4')->toContain('16 paneles');
    expect($project->fresh()->systemDesign->panel_count)->toBe(9);
    expect($project->fresh()->solar_string_count)->toBe(3);

    $lines[0]['adjustment'] = -9;
    $this->postJson(route('admin.solar-projects.quote.preview', $project), ['lines' => $lines])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lines.0.adjustment');
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
    expect($quote->items[0]->quantity)->toBe(12);
    expect((float) $quote->items[0]->line_total)->toBe(12852.0);
    expect($quote->items[1]->base_quantity)->toBe(0);
    expect((float) $quote->items[1]->line_total)->toBe(2000.0);
    expect($quote->items[2]->is_labor)->toBeTrue();
    expect($quote->items[2]->name)->toBe('Mano de obra');
    expect($quote->items[2]->quantity)->toBe(1);
    expect((float) $quote->items[2]->line_total)->toBe(3570.0);
    expect((float) $quote->subtotal)->toBe(17000.0);
    expect((float) $quote->tax_total)->toBe(2850.0);
    expect((float) $quote->discount_total)->toBe(1428.0);
    expect((float) $quote->total)->toBe(18422.0);

    $this->get(route('admin.solar-projects.quotes.show', [$project, $quote->id]))->assertOk()->assertSee('Tornillos')->assertSee('Mano de obra')->assertSee('Incluye instalación');
    $this->post(route('admin.solar-projects.quote.store', $project), [
        'name' => 'Segunda',
        'labor' => ['unit_price' => 0, 'tax_percent' => 0, 'discount_percent' => 0],
        'lines' => [0 => ['item' => 'material:'.$screw->id, 'line_key' => '', 'adjustment' => 1, 'tax_percent' => 19, 'discount_percent' => 0]],
    ])->assertRedirect();
    $this->get(route('admin.solar-projects.quote.show', $project))->assertSee('N.° '.$quote->folio)->assertSee('Cotización residencial');
    $this->assertDatabaseCount('solar_project_quotes', 2);
});

test('deleting a quote removes its items, preserves other quotes and is scoped to its project', function () {
    [$project] = makeQuoteScenario($this);
    $quote = $project->quotes()->create(['name' => 'Eliminar', 'subtotal' => 0, 'tax_total' => 0, 'discount_total' => 0, 'total' => 0]);
    $otherQuote = $project->quotes()->create(['name' => 'Conservar', 'subtotal' => 0, 'tax_total' => 0, 'discount_total' => 0, 'total' => 0]);
    $item = $quote->items()->create([
        'position' => 1,
        'name' => 'Panel de prueba',
        'concept' => 'Paneles solares',
        'base_quantity' => 1,
        'adjustment' => 0,
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_percent' => 0,
        'discount_percent' => 0,
        'line_total' => 1000,
    ]);

    $this->get(route('admin.solar-projects.quote.show', $project))
        ->assertOk()
        ->assertSee('Eliminar');
    $this->get(route('admin.solar-projects.quotes.show', [$project, $quote->id]))
        ->assertOk()
        ->assertSee('Eliminar cotización');

    $this->delete(route('admin.solar-projects.quotes.destroy', [$project, $quote->id]))
        ->assertRedirect(route('admin.solar-projects.quote.show', $project))
        ->assertSessionHas('success', 'Cotización N.° '.$quote->folio.' eliminada.');

    $this->assertDatabaseMissing('solar_project_quotes', ['id' => $quote->id]);
    $this->assertDatabaseMissing('solar_project_quote_items', ['id' => $item->id]);
    $this->assertDatabaseHas('solar_project_quotes', ['id' => $otherQuote->id]);

    $otherProject = SolarProject::factory()->create();
    $foreignQuote = $otherProject->quotes()->create(['name' => 'Otro proyecto', 'subtotal' => 0, 'tax_total' => 0, 'discount_total' => 0, 'total' => 0]);

    $this->delete(route('admin.solar-projects.quotes.destroy', [$project, $foreignQuote->id]))
        ->assertNotFound();
    $this->assertDatabaseHas('solar_project_quotes', ['id' => $foreignQuote->id]);
});

test('panel adjustments create an MPPT-valid quote scenario and freeze its diagram without changing the project', function () {
    [$project, $payload] = makeProtectionScenario();
    $this->put(route('admin.solar-projects.system-design.update', $project), $payload)->assertRedirect();
    $design = $project->fresh()->systemDesign;
    $panel = $design->solarPanel;
    $inverter = $design->inverter;
    $battery = $design->battery;

    $response = $this->post(route('admin.solar-projects.quote.store', $project), [
        'name' => 'Escenario ampliado',
        'labor' => ['unit_price' => 0, 'tax_percent' => 0, 'discount_percent' => 0],
        'lines' => [
            0 => ['item' => 'equipment:'.$panel->id, 'line_key' => 'panel', 'adjustment' => 5, 'tax_percent' => 0, 'discount_percent' => 0],
            1 => ['item' => 'equipment:'.$inverter->id, 'line_key' => 'inverter', 'adjustment' => 0, 'tax_percent' => 0, 'discount_percent' => 0],
            2 => ['item' => 'equipment:'.$battery->id, 'line_key' => 'battery', 'adjustment' => 0, 'tax_percent' => 0, 'discount_percent' => 0],
        ],
    ]);
    $quote = SolarProjectQuote::query()->with('items')->firstOrFail();
    $response->assertRedirect(route('admin.solar-projects.quotes.show', [$project, $quote->id]));

    expect($quote->snapshot['design']['panel_count'])->toBe(16);
    expect($quote->snapshot['design']['inverter_count'])->toBe(4);
    expect($quote->snapshot['design']['string_count'])->toBe(4);
    expect($quote->snapshot['design']['panels_per_string'])->toBe(4);
    expect($quote->snapshot['diagram']['string_layout'])->toHaveCount(4);
    expect($quote->snapshot['materials']['ground_connectors'])->toBe(8);
    expect(collect($quote->snapshot['protections'])->firstWhere('title', 'Breakers DC para strings')['quantity'])->toBe(4);
    expect($quote->items->firstWhere('concept', 'Paneles solares')->quantity)->toBe(16);
    expect($quote->items->firstWhere('concept', 'Inversores')->quantity)->toBe(4);
    expect($project->fresh()->systemDesign->panel_count)->toBe(9);
    expect($project->fresh()->solar_string_count)->toBe(3);

    $html = view('admin.solar-projects.quote.sheet', ['quote' => $quote, 'snapshot' => $quote->snapshot])->render();
    expect($html)->toContain('Diagrama del sistema')->toContain('String 4')->toContain('Paneles solares');
    expect(strpos($html, '<h3>Breakers y protecciones</h3>'))->toBeLessThan(strpos($html, 'Diagrama del sistema'));
    expect(substr_count($html, public_path('images/diagrama/panel.png')))->toBe(16);
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
    $project->update(['project_warranty' => 'Garantía vigente al cotizar']);
    $this->post(route('admin.solar-projects.quote.store', $project), [
        'name' => 'Cotización congelada',
        'labor' => ['unit_price' => 1000, 'tax_percent' => 0, 'discount_percent' => 0],
    ])->assertRedirect();

    $this->post(route('admin.solar-projects.loads.store', $project), $load($horno))->assertRedirect();
    $project->update(['project_warranty' => 'Garantía modificada después']);
    $quote = SolarProjectQuote::query()->with('items')->firstOrFail();

    expect($project->fresh()->total_consumption_wh_per_day)->not->toBe('1000.00');
    expect($quote->snapshot['loads'])->toHaveCount(1);
    expect($quote->snapshot['loads'][0]['name'])->toBe('Nevera de prueba');
    expect($quote->snapshot['consumption']['total_wh'])->toEqual(1000);
    expect($quote->snapshot['design']['panel']['name'])->toBe('Panel de prueba');
    expect($quote->snapshot['design']['panel_count'])->toBe(2);
    expect($quote->snapshot['project']['warranty'])->toBe('Garantía vigente al cotizar');

    $html = view('admin.solar-projects.quote.sheet', ['quote' => $quote, 'snapshot' => $quote->snapshot])->render();
    expect($html)->toContain('Nevera de prueba')->toContain('Cotización congelada')->toContain('Ficha de cotización N.° 31')->toContain('Garantía vigente al cotizar')->not->toContain('Horno de prueba')->not->toContain('Garantía modificada después');
    expect(strpos($html, 'Garantía del proyecto'))->toBeGreaterThan(strpos($html, 'Total (COP)'));

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
