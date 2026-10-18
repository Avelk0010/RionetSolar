@extends('layouts.app')

@section('title', $project->name.' · Cotización')

@section('content')
    @include('admin.solar-projects._workspace-header', ['activeModule' => 'quote'])

    @if(! $ready)
        <div role="status" class="border-l-4 border-[#f3bd35] bg-[#fff9e9] px-4 py-3 text-sm text-[#6e5a22]">Calcula primero el <a href="{{ route('admin.solar-projects.system-design.show', $project) }}" class="font-semibold underline">dimensionamiento</a> del proyecto para generar la cotización.</div>
    @else
        <section class="mb-8 border-y border-[#dce6e1] bg-white p-5 sm:p-7">
            <div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Submódulo 3</p><h2 class="mt-1 text-lg font-semibold text-[#173b49]">Nueva cotización</h2><p class="mt-1 text-sm text-[#687b80]">Cantidad final = cantidad del dimensionamiento + (+/-). Valor total = cantidad final × valor unitario, más impuesto, menos descuento.</p></div>

            @if(count($missing))
                <div role="status" class="mb-5 border-l-4 border-[#f3bd35] bg-[#fff9e9] px-4 py-3 text-sm text-[#6e5a22]">No hay registro activo en el catálogo para: {{ implode(', ', $missing) }}. No se incluyeron; puedes agregarlos con "Agregar ítem" cuando existan.</div>
            @endif

            <form method="POST" action="{{ route('admin.solar-projects.quote.store', $project) }}" data-quote-form data-next-index="{{ count($rows) }}" novalidate>
                @csrf
                @error('lines')<p class="mb-3 text-sm text-[#b43f35]">{{ $message }}</p>@enderror
                <div class="mb-6 grid gap-5 sm:grid-cols-2">
                    <x-input-field name="name" label="Nombre de la cotización" :value="old('name')" placeholder="Ej. Sistema residencial – Fam. Pérez" required />
                    <x-input-field name="description" label="Descripción" type="textarea" :rows="2" :value="old('description')" />
                </div>
                <div class="overflow-x-auto border border-[#dce6e1]">
                    <table class="w-full min-w-[980px] text-left text-sm">
                        <thead class="border-b border-[#dce6e1] bg-[#f4f8f3] text-xs uppercase tracking-wide text-[#597078]"><tr><th class="px-3 py-3 font-semibold">#</th><th class="px-3 py-3 font-semibold">Ítem</th><th class="px-3 py-3 text-right font-semibold">Cantidad</th><th class="px-3 py-3 font-semibold">+/-</th><th class="px-3 py-3 text-right font-semibold">Valor unitario (COP)</th><th class="px-3 py-3 font-semibold">Impuesto (%)</th><th class="px-3 py-3 font-semibold">Descuento (%)</th><th class="px-3 py-3 text-right font-semibold">Valor total (COP)</th><th class="px-3 py-3"></th></tr></thead>
                        <tbody class="divide-y divide-[#e8eeea]" data-quote-rows>
                            @foreach($rows as $index => $row)
                                @include('admin.solar-projects.quote._row', ['row' => $row, 'index' => $index, 'options' => $options])
                            @endforeach
                        </tbody>
                        <tbody class="divide-y divide-[#e8eeea] border-t border-[#e8eeea]">@include('admin.solar-projects.quote._labor-row')</tbody>
                        <tfoot class="border-t-2 border-[#dce6e1] bg-[#f8faf7] font-semibold text-[#173b49]"><tr><td colspan="7" class="px-3 py-3 text-right">Total de la cotización</td><td class="px-3 py-3 text-right" data-grand-total></td><td></td></tr></tfoot>
                    </table>
                </div>
                <template data-quote-row-template>
                    @include('admin.solar-projects.quote._row', ['row' => ['item' => '', 'key' => null, 'name' => '', 'concept' => null, 'base' => 0, 'adjustment' => 1, 'unit_price' => 0, 'tax' => 0, 'discount' => 0, 'manual' => true], 'index' => '__INDEX__', 'options' => $options])
                </template>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <button type="button" data-add-quote-row class="button-secondary rounded-md bg-white px-4 py-2 text-sm font-semibold">+ Agregar ítem</button>
                    <button class="button-primary rounded-md px-5 py-2.5 text-sm font-semibold">Guardar cotización</button>
                </div>
            </form>
        </section>

        <section aria-labelledby="quote-history-title">
            <h2 id="quote-history-title" class="mb-3 text-lg font-semibold text-[#173b49]">Cotizaciones guardadas</h2>
            <div class="overflow-x-auto border border-[#dce6e1] bg-white">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead class="border-b border-[#dce6e1] bg-[#f4f8f3] text-xs uppercase tracking-wide text-[#597078]"><tr><th class="px-4 py-3 font-semibold">Cotización</th><th class="px-4 py-3 font-semibold">Fecha</th><th class="px-4 py-3 text-right font-semibold">Total (COP)</th><th class="px-4 py-3 text-right font-semibold">Acción</th></tr></thead>
                    <tbody class="divide-y divide-[#e8eeea]">
                        @forelse($quotes as $quote)
                            <tr><td class="px-4 py-3 font-semibold text-[#173b49]">N.° {{ $quote->id }}@if($quote->name) · {{ $quote->name }}@endif</td><td class="px-4 py-3">{{ $quote->created_at->format('d/m/Y H:i') }}</td><td class="px-4 py-3 text-right">$ {{ number_format((float) $quote->total, 2, ',', '.') }}</td><td class="px-4 py-3 text-right"><div class="flex justify-end gap-3 text-xs font-semibold"><a href="{{ route('admin.solar-projects.quotes.show', [$project, $quote->id]) }}" class="text-[#155a91] hover:underline">Ver</a>@if($quote->snapshot !== null)<a href="{{ route('admin.solar-projects.quotes.sheet', [$project, $quote->id]) }}" target="_blank" rel="noopener" class="text-[#557c39] hover:underline">Ficha</a>@endif</div></td></tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-[#718187]">Todavía no hay cotizaciones guardadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
