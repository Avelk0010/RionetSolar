<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSolarProjectQuoteRequest;
use App\Models\SolarProject;
use App\Services\SolarProjectQuoteBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SolarProjectQuoteController extends Controller
{
    public function show(SolarProject $solarProject, SolarProjectQuoteBuilder $builder): View
    {
        $solarProject->load('systemDesign');
        $design = $solarProject->systemDesign;
        $ready = $design?->panel_count !== null;
        $rows = [];
        $missing = [];

        if ($ready) {
            $auto = $builder->autoLines($solarProject, $design);
            $missing = $auto['missing'];
            $rows = session()->hasOldInput() ? $this->rowsFromOldInput($builder, collect($auto['lines'])->keyBy('key')) : $this->rowsFromAutoLines($auto['lines']);
            $scenario = $builder->quoteScenario($solarProject, $design, []);
        }

        return view('admin.solar-projects.quote.show', [
            'project' => $solarProject,
            'ready' => $ready,
            'rows' => $rows,
            'missing' => $missing,
            'diagram' => $scenario['diagram'] ?? null,
            'previewUrl' => route('admin.solar-projects.quote.preview', $solarProject),
            'options' => $ready ? $builder->itemOptions() : [],
            'quotes' => $solarProject->quotes()->get(),
        ]);
    }

    public function preview(Request $request, SolarProject $solarProject, SolarProjectQuoteBuilder $builder): JsonResponse
    {
        $design = $solarProject->systemDesign;

        abort_if($design?->panel_count === null, 422, 'Calcula primero el dimensionamiento del proyecto.');

        $validated = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.item' => ['required', 'string', 'regex:/^(equipment|material):[1-9]\d*$/'],
            'lines.*.line_key' => ['nullable', 'string', 'max:40'],
            'lines.*.adjustment' => ['required', 'integer', 'between:-100000,100000'],
        ]);

        $scenario = $builder->quoteScenario($solarProject, $design, $validated['lines']);

        return response()->json([
            'diagram_html' => view('admin.solar-projects.quote._diagram-preview', ['diagram' => $scenario['diagram']])->render(),
            'auto_lines' => $scenario['auto_lines'],
            'effective_adjustments' => $scenario['effective_adjustments'],
            'requested_panel_count' => $scenario['requested_panel_count'],
            'balanced_panel_count' => (int) $scenario['design']->panel_count,
        ]);
    }

    public function store(SaveSolarProjectQuoteRequest $request, SolarProject $solarProject, SolarProjectQuoteBuilder $builder): RedirectResponse
    {
        $design = $solarProject->systemDesign;

        if ($design?->panel_count === null) {
            return redirect()->route('admin.solar-projects.quote.show', $solarProject)
                ->withErrors(['lines' => 'Calcula primero el dimensionamiento del proyecto.']);
        }

        $quote = $builder->save(
            $solarProject,
            $design,
            $request->safe()->only(['name', 'description']),
            $request->validated('lines', []),
            $request->validated('labor'),
        );

        return redirect()->route('admin.solar-projects.quotes.show', [$solarProject, $quote->id])
            ->with('success', 'Cotización guardada.');
    }

    public function sheet(SolarProject $solarProject, int $quote): Response
    {
        $quote = $solarProject->quotes()->with('items')->findOrFail($quote);

        abort_if($quote->snapshot === null, 404, 'Esta cotización no tiene datos congelados para generar la ficha.');

        return Pdf::loadView('admin.solar-projects.quote.sheet', ['quote' => $quote, 'snapshot' => $quote->snapshot])
            ->setPaper('a4')
            ->stream('ficha-cotizacion-'.$quote->folio.'.pdf');
    }

    public function showSaved(SolarProject $solarProject, int $quote): View
    {
        return view('admin.solar-projects.quote.saved', [
            'project' => $solarProject,
            'quote' => $solarProject->quotes()->with('items')->findOrFail($quote),
        ]);
    }

    public function destroy(SolarProject $solarProject, int $quote): RedirectResponse
    {
        $quote = $solarProject->quotes()->findOrFail($quote);
        $folio = $quote->folio;
        $quote->delete();

        return redirect()->route('admin.solar-projects.quote.show', $solarProject)
            ->with('success', 'Cotización N.° '.$folio.' eliminada.');
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    private function rowsFromAutoLines(array $lines): array
    {
        return array_map(fn (array $line) => [
            'item' => $line['item'],
            'key' => $line['key'],
            'name' => $line['name'],
            'concept' => $line['concept'],
            'base' => $line['quantity'],
            'adjustment' => 0,
            'unit_price' => $line['unit_price'],
            'tax' => 0,
            'discount' => 0,
            'manual' => false,
            'editable' => $line['key'] === 'panel',
            'removable' => false,
        ], $lines);
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $auto
     * @return list<array<string, mixed>>
     */
    private function rowsFromOldInput(SolarProjectQuoteBuilder $builder, $auto): array
    {
        $rows = [];

        foreach ((array) old('lines', []) as $line) {
            $item = (string) ($line['item'] ?? '');
            $described = $item !== '' ? $builder->describeItem($item) : null;
            $autoLine = $auto->get($line['line_key'] ?? '');
            $isAuto = $autoLine !== null && $autoLine['item'] === $item;

            $rows[] = [
                'item' => $item,
                'key' => $isAuto ? $autoLine['key'] : null,
                'name' => $described['name'] ?? '',
                'concept' => $isAuto ? $autoLine['concept'] : null,
                'base' => $isAuto ? $autoLine['quantity'] : 0,
                'adjustment' => $line['adjustment'] ?? 0,
                'unit_price' => $described['price'] ?? 0,
                'tax' => $line['tax_percent'] ?? 0,
                'discount' => $line['discount_percent'] ?? 0,
                'manual' => ! $isAuto,
                'editable' => $isAuto ? $autoLine['key'] === 'panel' : true,
                'removable' => ! $isAuto,
            ];
        }

        return $rows;
    }
}
