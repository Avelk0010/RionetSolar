@extends('layouts.app')

@section('title', 'Electrodomésticos')

@section('content')
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div><p class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Datos maestros / Cargas</p><h1 class="text-2xl font-semibold text-[#12384a]">Electrodomésticos</h1><p class="mt-1 text-sm text-[#687b80]">{{ $appliances->total() }} registros en el catálogo</p></div>
        <a href="{{ route('admin.appliances.create') }}" class="button-primary inline-flex items-center rounded-md px-4 py-2.5 text-sm font-semibold">+ Nuevo electrodoméstico</a>
    </div>
    <form method="GET" action="{{ route('admin.appliances.index') }}" class="mb-5 grid gap-3 border-y border-[#dce6e1] bg-white/80 p-4 sm:grid-cols-2 lg:grid-cols-[minmax(180px,1fr)_160px_170px_auto]">
        <label class="sr-only" for="search">Buscar por nombre</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre" class="field-control min-w-0 rounded-md px-3 py-2 text-sm">
        <label class="sr-only" for="voltage-filter">Filtrar por voltaje</label><select id="voltage-filter" name="voltage" class="field-control rounded-md px-3 py-2 text-sm"><option value="">Todos los voltajes</option><option value="110" @selected(request('voltage') === '110')>110 V</option><option value="220" @selected(request('voltage') === '220')>220 V</option></select>
        <label class="sr-only" for="status-filter">Filtrar por estado</label><select id="status-filter" name="status" class="field-control rounded-md px-3 py-2 text-sm"><option value="">Todos los estados</option><option value="active" @selected(request('status') === 'active')>Activo</option><option value="inactive" @selected(request('status') === 'inactive')>Inactivo</option></select>
        <button class="button-secondary rounded-md bg-white px-4 py-2 text-sm font-semibold">Filtrar</button>
    </form>
    <div class="overflow-x-auto border border-[#dce6e1] bg-white">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-[#dce6e1] bg-[#f4f8f3] text-xs uppercase tracking-wide text-[#597078]"><tr><th class="px-4 py-3 font-semibold">Nombre</th><th class="px-4 py-3 font-semibold">Voltaje</th><th class="px-4 py-3 font-semibold">Consumo (Wh/1 h)</th><th class="px-4 py-3 font-semibold">Estado</th><th class="px-4 py-3 text-right font-semibold">Acciones</th></tr></thead>
            <tbody class="divide-y divide-[#e8eeea]">
                @forelse($appliances as $appliance)
                    <tr class="hover:bg-[#fbfdf9]"><td class="px-4 py-3.5 font-semibold text-[#173b49]">{{ $appliance->name }}</td><td class="px-4 py-3.5">{{ $appliance->voltage }} V</td><td class="px-4 py-3.5">{{ number_format((float) $appliance->energy_consumption_wh, 2, ',', '.') }} Wh</td><td class="px-4 py-3.5"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $appliance->is_active ? 'bg-[#e8f3e3] text-[#3e7136]' : 'bg-[#edf0ef] text-[#66777b]' }}">{{ $appliance->is_active ? 'Activo' : 'Inactivo' }}</span></td><td class="px-4 py-3.5"><div class="flex justify-end gap-3 text-xs font-semibold"><a class="text-[#155a91] hover:underline" href="{{ route('admin.appliances.show', $appliance) }}">Ver</a><a class="text-[#557c39] hover:underline" href="{{ route('admin.appliances.edit', $appliance) }}">Editar</a>@if($appliance->is_active)<form method="POST" action="{{ route('admin.appliances.destroy', $appliance) }}" onsubmit="return confirm('¿Desactivar este electrodoméstico? El registro se conservará.');">@csrf @method('DELETE')<button class="text-[#a5443a] hover:underline">Desactivar</button></form>@endif</div></td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-[#718187]">No hay electrodomésticos que coincidan con estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $appliances->links() }}</div>
@endsection