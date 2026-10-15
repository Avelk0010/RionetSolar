@extends('layouts.app')

@section('title', $project->name.' · Dimensionamiento')

@section('content')
    @include('admin.solar-projects._workspace-header', ['activeModule' => 'design'])

    <section class="mb-7 grid gap-3 sm:grid-cols-3" aria-label="Datos de consumo del proyecto">
        <div class="border border-[#dce6e1] bg-white p-4"><p class="text-xs text-[#718187]">Horas solares pico</p><p class="mt-1 text-lg font-semibold text-[#155a91]">{{ number_format((float) $project->solar_hours_per_day, 2, ',', '.') }} h/día</p></div>
        <div class="border border-[#dce6e1] bg-white p-4"><p class="text-xs text-[#718187]">Energía diaria</p><p class="mt-1 text-lg font-semibold text-[#155a91]">{{ number_format((float) $project->total_consumption_wh_per_day, 2, ',', '.') }} Wh/día</p></div>
        <div class="border border-[#dce6e1] bg-white p-4"><p class="text-xs text-[#718187]">Pico simultáneo</p><p class="mt-1 text-lg font-semibold text-[#155a91]">{{ $project->maximum_hourly_power_w !== null ? number_format((float) $project->maximum_hourly_power_w, 2, ',', '.').' W' : 'Pendiente' }}</p></div>
    </section>

    <section class="mb-8 border-y border-[#dce6e1] bg-white p-5 sm:p-7">
        <div class="mb-6"><p class="text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Submódulo 2</p><h2 class="mt-1 text-lg font-semibold text-[#173b49]">Equipos y parámetros</h2><p class="mt-1 text-sm text-[#687b80]">Las baterías se dimensionan usando capacidad Ah × voltaje V para obtener energía nominal Wh.</p></div>
        <form method="POST" action="{{ route('admin.solar-projects.system-design.update', $project) }}" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @csrf @method('PUT')
            <div class="space-y-1.5">
                <label for="solar_panel_equipment_id" class="block text-sm font-medium text-[#294751]">Panel solar <span class="text-[#bd5146]">*</span></label>
                <select id="solar_panel_equipment_id" name="solar_panel_equipment_id" required class="field-control block w-full rounded-md px-3 py-2.5 text-sm"><option value="">Selecciona un panel</option>@foreach($panels as $panel)<option value="{{ $panel->id }}" @selected((string) old('solar_panel_equipment_id', $design?->solar_panel_equipment_id) === (string) $panel->id)>{{ $panel->name }} · {{ number_format((float) $panel->solarPanelSpec->max_power_w, 0, ',', '.') }} W</option>@endforeach</select>
                @error('solar_panel_equipment_id')<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror
            </div>
            <div class="space-y-1.5">
                <label for="inverter_equipment_id" class="block text-sm font-medium text-[#294751]">Inversor <span class="text-[#bd5146]">*</span></label>
                <select id="inverter_equipment_id" name="inverter_equipment_id" required class="field-control block w-full rounded-md px-3 py-2.5 text-sm"><option value="">Selecciona un inversor</option>@foreach($inverters as $inverter)<option value="{{ $inverter->id }}" @selected((string) old('inverter_equipment_id', $design?->inverter_equipment_id) === (string) $inverter->id)>{{ $inverter->name }} · {{ number_format((float) $inverter->inverterSpec->max_output_power_w, 0, ',', '.') }} W</option>@endforeach</select>
                @error('inverter_equipment_id')<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror
            </div>
            <div class="space-y-1.5">
                <label for="battery_equipment_id" class="block text-sm font-medium text-[#294751]">Batería <span class="text-[#bd5146]">*</span></label>
                <select id="battery_equipment_id" name="battery_equipment_id" required class="field-control block w-full rounded-md px-3 py-2.5 text-sm"><option value="">Selecciona una batería</option>@foreach($batteries as $battery)<option value="{{ $battery->id }}" @selected((string) old('battery_equipment_id', $design?->battery_equipment_id) === (string) $battery->id)>{{ $battery->name }} · {{ number_format((float) $battery->batterySpec->capacity_ah, 0, ',', '.') }} Ah / {{ number_format((float) $battery->batterySpec->voltage_v, 0, ',', '.') }} V</option>@endforeach</select>
                @error('battery_equipment_id')<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror
            </div>
            <x-input-field name="total_oversizing_percent" label="Sobredimensionamiento total (%)" type="number" step="0.01" min="0" max="999.99" :value="old('total_oversizing_percent', $design?->total_oversizing_percent ?? 0)" required />
            <x-input-field name="night_oversizing_percent" label="Sobredimensionamiento nocturno (%)" type="number" step="0.01" min="0" max="999.99" :value="old('night_oversizing_percent', $design?->night_oversizing_percent ?? 0)" required />
            <x-input-field name="max_battery_discharge_percent" label="Descarga máxima de batería (%)" type="number" step="0.01" min="0.01" max="100" :value="old('max_battery_discharge_percent', $design?->max_battery_discharge_percent ?? 80)" required />
            <div class="sm:col-span-2 xl:col-span-3 flex justify-end border-t border-[#e8eeea] pt-5"><button class="button-primary rounded-md px-5 py-2.5 text-sm font-semibold">Guardar y calcular</button></div>
        </form>
    </section>

    <section aria-labelledby="design-results-title">
        <div class="mb-4"><p class="text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Resultado</p><h2 id="design-results-title" class="mt-1 text-lg font-semibold text-[#173b49]">Dimensionamiento fotovoltaico</h2></div>
        @if($design?->panel_count !== null)
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="border border-[#dce6e1] bg-white p-5"><p class="text-xs text-[#718187]">Paneles solares</p><p class="mt-1 text-2xl font-semibold text-[#155a91]">{{ $design->panel_count }}</p></div>
                <div class="border border-[#dce6e1] bg-white p-5"><p class="text-xs text-[#718187]">Inversores</p><p class="mt-1 text-2xl font-semibold text-[#155a91]">{{ $design->inverter_count }}</p></div>
                <div class="border border-[#dce6e1] bg-white p-5"><p class="text-xs text-[#718187]">Baterías</p><p class="mt-1 text-2xl font-semibold text-[#155a91]">{{ $design->battery_count }}</p></div>
                <div class="border border-[#dce6e1] bg-white p-5"><p class="text-xs text-[#718187]">Strings solares</p><p class="mt-1 text-2xl font-semibold text-[#155a91]">{{ $project->solar_string_count }}</p></div>
            </div>
            <div class="mt-5 border-y border-[#dce6e1] bg-white p-5">
                <div class="flex flex-wrap justify-between gap-3"><h3 class="font-semibold text-[#173b49]">Distribución de paneles</h3><span class="text-sm text-[#687b80]">Potencia total del arreglo: {{ number_format((float) $design->array_power_w, 2, ',', '.') }} W</span></div>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                    @for($stringNumber = 1; $stringNumber <= $project->solar_string_count; $stringNumber++)
                        <li class="border border-[#dce6e1] bg-[#f8faf7] px-4 py-3 text-sm"><span class="font-semibold text-[#155a91]">String {{ $stringNumber }}</span><span class="ml-2 text-[#526a70]">{{ $design->panels_per_string }} paneles</span></li>
                    @endfor
                </ul>
                <p class="mt-3 text-xs text-[#718187]">La cantidad de paneles se redondeó hacia arriba para que todos los strings tengan el mismo número.</p>
            </div>
        @elseif($design)
            <div role="status" class="border-l-4 border-[#f3bd35] bg-[#fff9e9] px-4 py-3 text-sm text-[#6e5a22]">El consumo del proyecto cambió. Guarda el dimensionamiento nuevamente para recalcular las cantidades.</div>
        @else
            <div class="border border-dashed border-[#cbd9d4] bg-white px-5 py-10 text-center text-sm text-[#718187]">Selecciona los equipos y guarda los parámetros para ver las cantidades calculadas.</div>
        @endif
    </section>
@endsection