<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Ficha de cotización N.° {{ $quote->id }}</title>
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
</style>
</head>
<body>
@php
    $n = fn ($value, $decimals = 2) => number_format((float) $value, $decimals, ',', '.');
    $project = $snapshot['project'];
    $design = $snapshot['design'];
    $materialLabels = ['rails' => 'Riel estructura solar', 'rail_unions' => 'Uniones de riel', 'ground_connectors' => 'Conectores de tierra', 'mc4_connectors' => 'Conectores MC4', 'end_clamps' => 'End clamp', 'mid_clamps' => 'Mid clamp'];
@endphp

<table class="head">
    <tr>
        <td style="width: 40%;">@php($logo = public_path('images/logo-rionet-solar.png'))<img src="{{ file_exists($logo) ? $logo : public_path('rionet-solar.svg') }}" style="height: 54px;" alt="Rionet Solar"></td>
        <td class="r">
            <p class="small muted">FICHA DE COTIZACIÓN</p>
            <h1>N.° {{ $quote->id }} · {{ $quote->name }}</h1>
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
</body>
</html>
