@extends('layouts.app')

@section('title', $project->name.' · Cargas')

@section('content')
    @include('admin.solar-projects._workspace-header', ['activeModule' => 'loads'])

    <section class="mb-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Consumo calculado del proyecto">
        <div class="border border-[#dce6e1] bg-white p-4"><p class="text-xs text-[#718187]">Energía total diaria</p><p class="mt-1 text-lg font-semibold text-[#155a91]">{{ number_format((float) $project->total_consumption_wh_per_day, 2, ',', '.') }} Wh/día</p></div>
        <div class="border border-[#dce6e1] bg-white p-4"><p class="text-xs text-[#718187]">Energía nocturna diaria</p><p class="mt-1 text-lg font-semibold text-[#155a91]">{{ number_format((float) $project->night_consumption_wh_per_day, 2, ',', '.') }} Wh/día</p></div>
        <div class="border border-[#dce6e1] bg-white p-4"><p class="text-xs text-[#718187]">Potencia pico simultánea</p><p class="mt-1 text-lg font-semibold text-[#155a91]">{{ $project->maximum_hourly_power_w !== null ? number_format((float) $project->maximum_hourly_power_w, 2, ',', '.').' W' : 'Pendiente' }}</p></div>
        <div class="border border-[#dce6e1] bg-white p-4"><p class="text-xs text-[#718187]">Strings dimensionados</p><p class="mt-1 text-lg font-semibold text-[#155a91]">{{ $project->solar_string_count ?? 'Pendiente' }}</p></div>
    </section>

    <section class="mb-8 border-y border-[#dce6e1] bg-white p-5 sm:p-7">
        <div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Submódulo 1</p><h2 class="mt-1 text-lg font-semibold text-[#173b49]">Agregar electrodoméstico</h2></div>
        @if($availableAppliances->isEmpty())
            <p class="border-l-4 border-[#f3bd35] bg-[#fff9e9] px-4 py-3 text-sm text-[#6e5a22]">No hay electrodomésticos activos disponibles. Agrega uno en <a href="{{ route('admin.appliances.create') }}" class="font-semibold underline">el catálogo de electrodomésticos</a>.</p>
        @else
            <form method="POST" action="{{ route('admin.solar-projects.loads.store', $project) }}" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @csrf
                <div class="space-y-1.5">
                    <label for="appliance_id" class="block text-sm font-medium text-[#294751]">Electrodoméstico <span class="text-[#bd5146]">*</span></label>
                    <select id="appliance_id" name="appliance_id" required class="field-control block w-full rounded-md px-3 py-2.5 text-sm">
                        <option value="">Selecciona una carga</option>
                        @foreach($availableAppliances as $appliance)
                            <option value="{{ $appliance->id }}" @selected((string) old('appliance_id') === (string) $appliance->id)>{{ $appliance->name }} · {{ number_format((float) $appliance->energy_consumption_wh, 2, ',', '.') }} Wh/1 h</option>
                        @endforeach
                    </select>
                    @error('appliance_id')<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror
                </div>
                <x-input-field name="quantity_total" label="Cantidad total" type="number" step="1" min="1" :value="old('quantity_total', 1)" required />
                <x-input-field name="quantity_night" label="Cantidad de noche" type="number" step="1" min="0" :value="old('quantity_night', 0)" required />
                <x-input-field name="usage_hours_total" label="Horas de uso total al día" type="number" step="0.01" min="0" max="24" :value="old('usage_hours_total')" required />
                <x-input-field name="usage_hours_night" label="Horas de uso nocturno al día" type="number" step="0.01" min="0" max="24" :value="old('usage_hours_night', 0)" required />
                <div class="flex items-end"><button class="button-primary w-full rounded-md px-5 py-2.5 text-sm font-semibold">Agregar al proyecto</button></div>
                @foreach(['quantity_total', 'quantity_night', 'usage_hours_total', 'usage_hours_night'] as $field)
                    @error($field)<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror
                @endforeach
            </form>
        @endif
    </section>

    <section aria-labelledby="project-appliances-title">
        <div class="mb-4 flex items-end justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">{{ $items->count() }} cargas</p><h2 id="project-appliances-title" class="mt-1 text-lg font-semibold text-[#173b49]">Electrodomésticos del proyecto</h2></div></div>
        <div class="overflow-x-auto border border-[#dce6e1] bg-white">
            <table class="w-full min-w-[1180px] text-left text-sm">
                <thead class="border-b border-[#dce6e1] bg-[#f4f8f3] text-xs uppercase tracking-wide text-[#597078]"><tr><th class="px-3 py-3 font-semibold">Electrodoméstico</th><th class="px-3 py-3 font-semibold">Consumo (Wh/1 h)</th><th class="px-3 py-3 font-semibold">Cantidad total</th><th class="px-3 py-3 font-semibold">Cantidad noche</th><th class="px-3 py-3 font-semibold">Horas totales</th><th class="px-3 py-3 font-semibold">Horas noche</th><th class="px-3 py-3 font-semibold">Energía día (Wh)</th><th class="px-3 py-3 font-semibold">Energía noche (Wh)</th><th class="px-3 py-3 font-semibold">Pico (W)</th><th class="px-3 py-3 text-right font-semibold">Acción</th></tr></thead>
                <tbody class="divide-y divide-[#e8eeea]">
                    @forelse($items as $item)
                        <tr class="hover:bg-[#fbfdf9]"><td class="px-3 py-3 font-semibold text-[#173b49]">{{ $item->appliance->name }}</td><td class="px-3 py-3">{{ number_format((float) $item->appliance_energy_consumption_wh, 2, ',', '.') }}</td><td class="px-3 py-3">{{ $item->quantity_total }}</td><td class="px-3 py-3">{{ $item->quantity_night }}</td><td class="px-3 py-3">{{ number_format((float) $item->usage_hours_total, 2, ',', '.') }} h</td><td class="px-3 py-3">{{ number_format((float) $item->usage_hours_night, 2, ',', '.') }} h</td><td class="px-3 py-3">{{ number_format((float) $item->daily_energy_wh, 2, ',', '.') }}</td><td class="px-3 py-3">{{ number_format((float) $item->night_energy_wh, 2, ',', '.') }}</td><td class="px-3 py-3">{{ number_format((float) $item->peak_power_w, 2, ',', '.') }}</td><td class="px-3 py-3 text-right"><form method="POST" action="{{ route('admin.solar-projects.loads.destroy', [$project, $item]) }}" onsubmit="return confirm('¿Retirar este electrodoméstico del proyecto?');">@csrf @method('DELETE')<button class="text-xs font-semibold text-[#a5443a] hover:underline">Retirar</button></form></td></tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-12 text-center text-sm text-[#718187]">Aún no has agregado cargas a este proyecto.</td></tr>
                    @endforelse
                </tbody>
                @if($items->isNotEmpty())
                    <tfoot class="border-t-2 border-[#dce6e1] bg-[#f8faf7] font-semibold text-[#173b49]"><tr><td colspan="6" class="px-3 py-3 text-right">Totales del proyecto</td><td class="px-3 py-3">{{ number_format((float) $project->total_consumption_wh_per_day, 2, ',', '.') }} Wh/día</td><td class="px-3 py-3">{{ number_format((float) $project->night_consumption_wh_per_day, 2, ',', '.') }} Wh/día</td><td class="px-3 py-3">{{ $project->maximum_hourly_power_w !== null ? number_format((float) $project->maximum_hourly_power_w, 2, ',', '.').' W' : 'Pendiente' }}</td><td></td></tr></tfoot>
                @endif
            </table>
        </div>
    </section>
@endsection