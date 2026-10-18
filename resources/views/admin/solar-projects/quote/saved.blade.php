@extends('layouts.app')

@section('title', $project->name.' · Cotización N.° '.$quote->id)

@section('content')
    @include('admin.solar-projects._workspace-header', ['activeModule' => 'quote'])

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Cotización guardada</p><h2 class="mt-1 text-lg font-semibold text-[#173b49]">N.° {{ $quote->id }}@if($quote->name) · {{ $quote->name }}@endif</h2><p class="mt-1 text-xs text-[#718187]">{{ $quote->created_at->format('d/m/Y H:i') }}</p>@if($quote->description)<p class="mt-2 max-w-2xl whitespace-pre-line text-sm text-[#526a70]">{{ $quote->description }}</p>@endif</div>
        <div class="flex flex-wrap gap-2">@if($quote->snapshot !== null)<a href="{{ route('admin.solar-projects.quotes.sheet', [$project, $quote->id]) }}" target="_blank" rel="noopener" class="button-primary rounded-md px-4 py-2 text-sm font-semibold">Ficha (PDF)</a>@endif<a href="{{ route('admin.solar-projects.quote.show', $project) }}" class="button-secondary rounded-md bg-white px-4 py-2 text-sm font-semibold">← Volver a cotización</a></div>
    </div>
    <div class="overflow-x-auto border border-[#dce6e1] bg-white">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-[#dce6e1] bg-[#f4f8f3] text-xs uppercase tracking-wide text-[#597078]"><tr><th class="px-3 py-3 font-semibold">#</th><th class="px-3 py-3 font-semibold">Ítem</th><th class="px-3 py-3 text-right font-semibold">Cantidad</th><th class="px-3 py-3 text-right font-semibold">+/-</th><th class="px-3 py-3 text-right font-semibold">Valor unitario (COP)</th><th class="px-3 py-3 text-right font-semibold">Impuesto</th><th class="px-3 py-3 text-right font-semibold">Descuento</th><th class="px-3 py-3 text-right font-semibold">Valor total (COP)</th></tr></thead>
            <tbody class="divide-y divide-[#e8eeea]">
                @foreach($quote->items as $item)
                    <tr><td class="px-3 py-2.5 text-xs text-[#718187]">{{ $item->position }}</td><td class="px-3 py-2.5"><span class="font-semibold text-[#173b49]">{{ $item->name }}</span>@if($item->concept)<span class="block text-xs text-[#718187]">{{ $item->concept }}</span>@endif</td><td class="px-3 py-2.5 text-right">{{ $item->base_quantity }}</td><td class="px-3 py-2.5 text-right">{{ $item->adjustment > 0 ? '+' : '' }}{{ $item->adjustment }}</td><td class="px-3 py-2.5 text-right">$ {{ number_format((float) $item->unit_price, 2, ',', '.') }}</td><td class="px-3 py-2.5 text-right">{{ number_format((float) $item->tax_percent, 2, ',', '.') }} %</td><td class="px-3 py-2.5 text-right">{{ number_format((float) $item->discount_percent, 2, ',', '.') }} %</td><td class="px-3 py-2.5 text-right font-semibold">$ {{ number_format((float) $item->line_total, 2, ',', '.') }}</td></tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-[#dce6e1] bg-[#f8faf7] text-[#173b49]">
                <tr><td colspan="7" class="px-3 py-2 text-right">Subtotal</td><td class="px-3 py-2 text-right">$ {{ number_format((float) $quote->subtotal, 2, ',', '.') }}</td></tr>
                <tr><td colspan="7" class="px-3 py-2 text-right">Impuestos</td><td class="px-3 py-2 text-right">$ {{ number_format((float) $quote->tax_total, 2, ',', '.') }}</td></tr>
                <tr><td colspan="7" class="px-3 py-2 text-right">Descuentos</td><td class="px-3 py-2 text-right">− $ {{ number_format((float) $quote->discount_total, 2, ',', '.') }}</td></tr>
                <tr class="font-semibold"><td colspan="7" class="px-3 py-3 text-right">Total</td><td class="px-3 py-3 text-right">$ {{ number_format((float) $quote->total, 2, ',', '.') }}</td></tr>
            </tfoot>
        </table>
    </div>
@endsection
