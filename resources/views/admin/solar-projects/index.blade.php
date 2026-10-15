@extends('layouts.app')

@section('title', 'Proyectos solares')

@section('content')
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div><p class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Diseño fotovoltaico</p><h1 class="text-2xl font-semibold text-[#12384a]">Proyectos solares</h1><p class="mt-1 text-sm text-[#687b80]">{{ $projects->total() }} proyectos</p></div>
        <a href="{{ route('admin.solar-projects.create') }}" class="button-primary rounded-md px-4 py-2.5 text-sm font-semibold">+ Nuevo proyecto</a>
    </div>
    <form method="GET" action="{{ route('admin.solar-projects.index') }}" class="mb-5 flex gap-3 border-y border-[#dce6e1] bg-white/80 p-4">
        <label class="sr-only" for="search">Buscar proyectos</label>
        <input id="search" name="search" value="{{ request('search') }}" placeholder="Buscar proyecto" class="field-control min-w-0 flex-1 rounded-md px-3 py-2 text-sm">
        <button class="button-secondary rounded-md bg-white px-4 py-2 text-sm font-semibold">Buscar</button>
    </form>
    <div class="overflow-x-auto border border-[#dce6e1] bg-white">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="border-b border-[#dce6e1] bg-[#f4f8f3] text-xs uppercase tracking-wide text-[#597078]"><tr><th class="px-4 py-3 font-semibold">Proyecto</th><th class="px-4 py-3 font-semibold">Horas solares/día</th><th class="px-4 py-3 font-semibold">Cargas</th><th class="px-4 py-3 font-semibold">Consumo total</th><th class="px-4 py-3 text-right font-semibold">Acciones</th></tr></thead>
            <tbody class="divide-y divide-[#e8eeea]">
                @forelse($projects as $project)
                    <tr class="hover:bg-[#fbfdf9]"><td class="px-4 py-3.5 font-semibold text-[#173b49]">{{ $project->name }}</td><td class="px-4 py-3.5">{{ number_format((float) $project->solar_hours_per_day, 2, ',', '.') }} h</td><td class="px-4 py-3.5">{{ $project->appliance_items_count }}</td><td class="px-4 py-3.5">{{ number_format((float) $project->total_consumption_wh_per_day, 2, ',', '.') }} Wh/día</td><td class="px-4 py-3.5"><div class="flex justify-end gap-3 text-xs font-semibold"><a href="{{ route('admin.solar-projects.show', $project) }}" class="text-[#155a91] hover:underline">Abrir</a><a href="{{ route('admin.solar-projects.edit', $project) }}" class="text-[#557c39] hover:underline">Editar</a><form method="POST" action="{{ route('admin.solar-projects.destroy', $project) }}" onsubmit="return confirm('¿Eliminar este proyecto y sus cargas/configuración?');">@csrf @method('DELETE')<button class="text-[#a5443a] hover:underline">Eliminar</button></form></div></td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-[#718187]">Todavía no hay proyectos. Crea uno para comenzar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $projects->links() }}</div>
@endsection