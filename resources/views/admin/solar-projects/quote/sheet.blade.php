<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Ficha de cotización N.° {{ $quote->folio }}</title>
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #17313c; }
    h1 { font-size: 18px; margin: 0; color: #12384a; }
    h2 { font-size: 13px; margin: 22px 0 6px; padding-bottom: 3px; border-bottom: 2px solid #5f963f; color: #12384a; }
    h3 { font-size: 11px; margin: 12px 0 4px; color: #155a91; }
    p { margin: 3px 0; line-height: 1.45; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th { background: #eef4ec; color: #46636b; text-align: left; font-size: 8.5px; text-transform: uppercase; padding: 5px 6px; border: 1px solid #d5e2da; }
    td { padding: 5px 6px; border: 1px solid #d5e2da; vertical-align: top; }
    .r { text-align: right; }
    .muted { color: #6f8288; }
    .small { font-size: 8.5px; }
    .total td { font-weight: bold; background: #f6f9f4; }
    .head td { border: none; padding: 0; vertical-align: middle; }
    .box { border: 1px solid #d5e2da; background: #f8faf7; padding: 6px 8px; margin-top: 4px; }
    .warn { color: #6e5a22; background: #fff9e9; border-left: 3px solid #f3bd35; padding: 4px 6px; margin-top: 3px; }
    .break { page-break-before: always; }
    .diagram { border: 1px solid #cbdcc7; background: #f6faf4; padding: 10px; }
    .diagram table { margin: 0; table-layout: fixed; }
    .diagram td { text-align: center; vertical-align: middle; padding: 7px; }
    .diagram-heading { color: #477d36; font-size: 8px; font-weight: bold; }
    .diagram-title { color: #155a91; font-size: 12px; font-weight: bold; }
    .diagram-label { color: #38545c; font-size: 8px; font-weight: bold; }
    .diagram-muted { color: #6f8288; font-size: 7px; }
    .diagram-string { border: 1px solid #d5e2da; background: #fff; }
    .diagram-node { border: 1px solid #d5e2da; background: #fff; }
    .diagram-input { border-color: #e5bbbb; background: #fff8f7; }
    .diagram-center { border-color: #dfbb50; background: #fffaf0; }
    .diagram-output { border-color: #d9c9ed; background: #fff; }
    .diagram-arrow-ac { color: #d96161; font-size: 15px; font-weight: bold; }
    .diagram-arrow-dc { color: #5f963f; font-size: 12px; font-weight: bold; }
    .diagram-arrow-battery { color: #c49623; font-size: 13px; font-weight: bold; }
    .diagram-section { page-break-after: avoid; }
    .diagram { page-break-inside: avoid; }
    .diagram-equipment td { border: none; padding: 1px; }
</style>
</head>
<body>
@php
    $n = fn ($value, $decimals = 2) => number_format((float) $value, $decimals, ',', '.');
    $project = $snapshot['project'];
    $design = $snapshot['design'];
    $materialLabels = ['rails' => 'Riel estructura solar', 'rail_unions' => 'Uniones de riel', 'ground_connectors' => 'Conectores de tierra', 'mc4_connectors' => 'Conectores MC4', 'end_clamps' => 'End clamp', 'mid_clamps' => 'Mid clamp'];
    $diagram = $snapshot['diagram'] ?? [
        'panel_count' => $design['panel_count'],
        'inverter_count' => $design['inverter_count'],
        'battery_count' => $design['battery_count'],
        'string_count' => $design['string_count'],
        'panels_per_string' => $design['panels_per_string'],
        'string_layout' => $design['string_layout'],
    ];
    $diagramImages = [
        'panel' => public_path('images/diagrama/panel.png'),
        'protections' => public_path('images/diagrama/caja-protecciones.png'),
        'inverter' => public_path('images/diagrama/inversor.png'),
        'battery' => public_path('images/diagrama/bateria.png'),
        'grid' => public_path('images/diagrama/energia-publica.png'),
        'loads' => public_path('images/diagrama/cargas.png'),
    ];
@endphp

<table class="head">
    <tr>
        <td style="width: 40%;">@php($logo = public_path('images/logo-rionet-solar.png'))<img src="{{ file_exists($logo) ? $logo : public_path('rionet-solar.svg') }}" style="height: 54px;" alt="Rionet Solar"></td>
        <td class="r">
            <p class="small muted">FICHA DE COTIZACIÓN</p>
            <h1>N.° {{ $quote->folio }} · {{ $quote->name }}</h1>
            <p class="muted">{{ $quote->created_at->format('d/m/Y H:i') }}</p>
        </td>
    </tr>
</table>

<div class="box">
    <p><strong>Proyecto:</strong> {{ $project['name'] }}@if($project['description']) — {{ $project['description'] }}@endif</p>
    @if($quote->description)<p><strong>Cotización:</strong> {{ $quote->description }}</p>@endif
    <p><strong>Horas solares pico:</strong> {{ $n($project['solar_hours_per_day']) }} h/día @if($project['latitude'] !== null)· <strong>Coordenadas:</strong> {{ $n($project['latitude'], 6) }}, {{ $n($project['longitude'], 6) }}@endif</p>
</div>

<h2>1. Cargas eléctricas</h2>
<table>
    <thead><tr><th>Electrodoméstico</th><th class="r">Consumo (Wh/1 h)</th><th class="r">Cant. total</th><th class="r">Cant. noche</th><th class="r">Horas total</th><th class="r">Horas noche</th><th class="r">Energía día (Wh)</th><th class="r">Energía noche (Wh)</th><th class="r">Pico (W)</th></tr></thead>
    <tbody>
        @forelse($snapshot['loads'] as $load)
            <tr><td>{{ $load['name'] }}</td><td class="r">{{ $n($load['hourly_wh']) }}</td><td class="r">{{ $load['quantity_total'] }}</td><td class="r">{{ $load['quantity_night'] }}</td><td class="r">{{ $n($load['hours_total']) }}</td><td class="r">{{ $n($load['hours_night']) }}</td><td class="r">{{ $n($load['daily_wh']) }}</td><td class="r">{{ $n($load['night_wh']) }}</td><td class="r">{{ $n($load['peak_w']) }}</td></tr>
        @empty
            <tr><td colspan="9" class="muted">Sin cargas registradas.</td></tr>
        @endforelse
    </tbody>
    <tfoot><tr class="total"><td colspan="6" class="r">Totales del proyecto</td><td class="r">{{ $n($snapshot['consumption']['total_wh']) }} Wh/día</td><td class="r">{{ $n($snapshot['consumption']['night_wh']) }} Wh/día</td><td class="r">{{ $n($snapshot['consumption']['peak_w']) }} W</td></tr></tfoot>
</table>

<h2>2. Dimensionamiento</h2>
<table>
    <thead><tr><th>Componente</th><th>Equipo</th><th class="r">Cantidad</th><th>Características</th></tr></thead>
    <tbody>
        <tr><td>Paneles solares</td><td>{{ $design['panel']['name'] }}</td><td class="r">{{ $design['panel_count'] }}</td><td>{{ $n($design['panel']['power_w'], 0) }} W por panel · arreglo de {{ $n($design['array_power_w']) }} W</td></tr>
        <tr><td>Inversores</td><td>{{ $design['inverter']['name'] }}</td><td class="r">{{ $design['inverter_count'] }}</td><td>{{ $n($design['inverter']['output_w'], 0) }} W de salida · MPPT {{ $n($design['inverter']['mppt_w'], 0) }} W</td></tr>
        <tr><td>Baterías</td><td>{{ $design['battery']['name'] }}</td><td class="r">{{ $design['battery_count'] }}</td><td>{{ $n($design['battery']['capacity_ah'], 0) }} Ah · {{ $n($design['battery']['voltage_v'], 0) }} V</td></tr>
        <tr><td>Strings solares</td><td>—</td><td class="r">{{ $design['string_count'] }}</td><td>{{ $design['panels_per_string'] }} paneles por string</td></tr>
    </tbody>
</table>
<p class="small muted">Sobredimensionamiento total {{ $n($design['total_oversizing_percent']) }}% · nocturno {{ $n($design['night_oversizing_percent']) }}% · descarga máxima de batería {{ $n($design['max_battery_discharge_percent']) }}%.</p>

<h3>Distribución de paneles</h3>
<table>
    <thead><tr><th>String</th><th>Filas (paneles en serie)</th><th class="r">Paneles</th></tr></thead>
    <tbody>
        @foreach($design['string_layout'] as $stringNumber => $rows)
            <tr><td>String {{ $stringNumber }}</td><td>{{ implode(' + ', $rows) }} ({{ count($rows) }} {{ count($rows) === 1 ? 'fila' : 'filas' }})</td><td class="r">{{ array_sum($rows) }}</td></tr>
        @endforeach
    </tbody>
</table>

<h3>Materiales necesarios</h3>
<table>
    <thead><tr><th>Material</th><th class="r">Cantidad</th></tr></thead>
    <tbody>
        @foreach($materialLabels as $key => $label)
            <tr><td>{{ $label }}</td><td class="r">{{ $snapshot['materials'][$key] }}</td></tr>
        @endforeach
    </tbody>
</table>

<h3>Breakers y protecciones</h3>
<table>
    <thead><tr><th>Protección</th><th class="r">Cantidad</th><th>Equipo del catálogo</th><th class="r">Polos</th><th class="r">Voltaje (V)</th><th class="r">Corriente (A)</th></tr></thead>
    <tbody>
        @foreach($snapshot['protections'] as $protection)
            <tr>
                <td>{{ $protection['title'] }}</td>
                <td class="r">{{ $protection['quantity'] }}</td>
                @if($protection['item'])
                    <td>{{ $protection['item'] }}</td><td class="r">{{ $protection['poles'] }}</td><td class="r">{{ $n($protection['voltage_v']) }}</td><td class="r">{{ $n($protection['current_a']) }}</td>
                @else
                    <td colspan="4"><span class="warn">No hay ítems en el catálogo que cumplan con los requerimientos.</span></td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>

<h3 class="diagram-section">Diagrama del sistema</h3>
<div class="diagram">
    <p class="diagram-heading">CAMPO FOTOVOLTAICO</p>
    <p class="diagram-title">{{ $diagram['panel_count'] }} paneles · {{ $diagram['string_count'] }} strings · {{ $diagram['panels_per_string'] }} en serie por string</p>
    <table>
        @foreach(array_chunk($diagram['string_layout'], 2, true) as $stringPair)
            <tr>
                @foreach($stringPair as $stringNumber => $rows)
                    <td class="diagram-string" style="width: 50%;">
                        <p class="diagram-label">STRING {{ $stringNumber }} · {{ array_sum($rows) }} paneles en serie</p>
                        @foreach($rows as $rowIndex => $rowCount)
                            <p class="diagram-muted">Fila {{ $rowIndex + 1 }} · {{ $rowCount }} paneles</p>
                            @for($panelIndex = 0; $panelIndex < $rowCount; $panelIndex++)
                                <img src="{{ $diagramImages['panel'] }}" style="width: 25px; height: 25px; vertical-align: middle;" alt="Panel solar">
                            @endfor
                        @endforeach
                    </td>
                @endforeach
                @if(count($stringPair) === 1)<td style="width: 50%;"></td>@endif
            </tr>
        @endforeach
    </table>
    <table style="margin-top: 10px;">
        <tr>
            <td class="diagram-node diagram-input" style="width: 29%;">
                <img src="{{ $diagramImages['grid'] }}" style="width: 62px; height: 68px;" alt="Red eléctrica">
                <p class="diagram-label">RED ELÉCTRICA · AC INPUT</p>
                <p class="diagram-arrow-ac">↓</p>
                <img src="{{ $diagramImages['protections'] }}" style="width: 50px; height: 50px;" alt="Protección AC de entrada">
                <p class="diagram-label">BREAKER AC INPUT</p>
            </td>
            <td class="diagram-arrow-ac" style="width: 5%;">&rarr;</td>
            <td class="diagram-node diagram-center" style="width: 32%;">
                <img src="{{ $diagramImages['protections'] }}" style="width: 46px; height: 46px;" alt="Protecciones DC">
                <p class="diagram-label">PROTECCIONES DC</p>
                <p class="diagram-muted">{{ $diagram['string_count'] }} por string</p>
                <p class="diagram-arrow-dc">↓ DC</p>
                <table class="diagram-equipment">
                    @foreach(array_chunk(range(1, $diagram['inverter_count']), 2) as $inverterPair)
                        <tr>
                            @foreach($inverterPair as $inverterIndex)
                                <td><img src="{{ $diagramImages['inverter'] }}" style="width: 56px; height: 48px;" alt="Inversor {{ $inverterIndex }}"></td>
                            @endforeach
                            @if(count($inverterPair) === 1)<td></td>@endif
                        </tr>
                    @endforeach
                </table>
                <p class="diagram-title">{{ $diagram['inverter_count'] }} {{ $diagram['inverter_count'] === 1 ? 'inversor' : 'inversores' }}</p>
                <p class="diagram-muted">{{ $design['inverter']['name'] }}</p>
                <p class="diagram-arrow-battery">&harr; BANCO DE BATERÍAS</p>
                <table class="diagram-equipment">
                    @foreach(array_chunk(range(1, $diagram['battery_count']), 3) as $batteryPair)
                        <tr>
                            @foreach($batteryPair as $batteryIndex)
                                <td><img src="{{ $diagramImages['battery'] }}" style="width: 34px; height: 40px;" alt="Batería {{ $batteryIndex }}"></td>
                            @endforeach
                            @for($emptyCell = count($batteryPair); $emptyCell < 3; $emptyCell++)<td></td>@endfor
                        </tr>
                    @endforeach
                </table>
                <p class="diagram-muted">{{ $diagram['battery_count'] }} {{ $diagram['battery_count'] === 1 ? 'batería' : 'baterías' }}</p>
            </td>
            <td class="diagram-arrow-ac" style="width: 5%;">&rarr;</td>
            <td class="diagram-node diagram-output" style="width: 29%;">
                <img src="{{ $diagramImages['protections'] }}" style="width: 50px; height: 50px;" alt="Protección AC de salida">
                <p class="diagram-label">BREAKER AC OUTPUT</p>
                <p class="diagram-arrow-ac">↓</p>
                <img src="{{ $diagramImages['loads'] }}" style="width: 66px; height: 68px;" alt="Cargas eléctricas">
                <p class="diagram-label">CARGAS AC</p>
            </td>
        </tr>
    </table>
</div>

<h2 class="break">3. Cotización</h2>
<table>
    <thead><tr><th>#</th><th>Ítem</th><th class="r">Cantidad</th><th class="r">+/-</th><th class="r">Valor unitario</th><th class="r">Impuesto</th><th class="r">Descuento</th><th class="r">Valor total</th></tr></thead>
    <tbody>
        @foreach($quote->items as $item)
            <tr>
                <td>{{ $item->position }}</td>
                <td>{{ $item->name }}@if($item->concept)<p class="small muted">{{ $item->concept }}</p>@endif</td>
                <td class="r">{{ $item->base_quantity }}</td>
                <td class="r">{{ $item->is_labor ? '—' : (($item->adjustment > 0 ? '+' : '').$item->adjustment) }}</td>
                <td class="r">$ {{ $n($item->unit_price) }}</td>
                <td class="r">{{ $n($item->tax_percent) }} %</td>
                <td class="r">{{ $n($item->discount_percent) }} %</td>
                <td class="r">$ {{ $n($item->line_total) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr><td colspan="7" class="r">Subtotal</td><td class="r">$ {{ $n($quote->subtotal) }}</td></tr>
        <tr><td colspan="7" class="r">Impuestos</td><td class="r">$ {{ $n($quote->tax_total) }}</td></tr>
        <tr><td colspan="7" class="r">Descuentos</td><td class="r">− $ {{ $n($quote->discount_total) }}</td></tr>
        <tr class="total"><td colspan="7" class="r">Total (COP)</td><td class="r">$ {{ $n($quote->total) }}</td></tr>
    </tfoot>
</table>
<p class="small muted" style="margin-top: 10px;">Los datos de cargas y dimensionamiento corresponden al estado del proyecto al momento de guardar esta cotización ({{ \Illuminate\Support\Carbon::parse($snapshot['captured_at'])->format('d/m/Y H:i') }}).</p>

<h2>4. Garantía del proyecto</h2>
<div class="box" style="white-space: pre-line;">{{ $project['warranty'] ?? '' }}</div>
</body>
</html>
