@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
    <div class="mb-10 grid gap-8 border-b border-[#dce6e1] pb-9 lg:grid-cols-[minmax(0,1fr)_260px] lg:items-end">
        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-[0.16em] text-[#5f963f]">Administración · Datos maestros</p>
            <h1 class="max-w-3xl text-3xl font-semibold leading-tight text-[#12384a] sm:text-4xl">Rionet Solar <span class="font-normal text-[#578c42]">/ Catálogo</span></h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#687b80]">Inventario técnico y materiales para la preparación de proyectos fotovoltaicos.</p>
        </div>
        <div class="flex items-center justify-start lg:justify-end">
            <img src="{{ asset('rionet-solar.svg') }}" alt="Logotipo Rionet Solar" class="w-[220px] max-w-full">
        </div>
    </div>

    <section aria-labelledby="catalog-summary" class="mb-10">
        <div class="mb-4 flex items-end justify-between gap-4">
            <h2 id="catalog-summary" class="text-lg font-semibold text-[#173b49]">Resumen del catálogo</h2>
            <span class="text-xs text-[#718187]">Registros totales</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('admin.appliances.index') }}" class="group border border-[#dce6e1] bg-white p-5 transition hover:border-[#8ebc72]">
                <div class="mb-5 flex items-center justify-between">
                    <span class="text-sm font-medium text-[#526a70]">Electrodomésticos</span>
                    <span class="h-2 w-8 rounded-full bg-[#f3bd35]"></span>
                </div>
                <p class="text-3xl font-semibold text-[#155a91]">{{ number_format($applianceCount) }}</p>
                <span class="mt-2 block text-xs text-[#718187] group-hover:text-[#155a91]">Abrir catálogo <span aria-hidden="true">→</span></span>
            </a>
            <a href="{{ route('admin.electrical-equipment.index') }}" class="group border border-[#dce6e1] bg-white p-5 transition hover:border-[#8ebc72]">
                <div class="mb-5 flex items-center justify-between">
                    <span class="text-sm font-medium text-[#526a70]">Equipos eléctricos</span>
                    <span class="h-2 w-8 rounded-full bg-[#238c89]"></span>
                </div>
                <p class="text-3xl font-semibold text-[#155a91]">{{ number_format($equipmentCount) }}</p>
                <span class="mt-2 block text-xs text-[#718187] group-hover:text-[#155a91]">Abrir catálogo <span aria-hidden="true">→</span></span>
            </a>
            <a href="{{ route('admin.installation-materials.index') }}" class="group border border-[#dce6e1] bg-white p-5 transition hover:border-[#8ebc72]">
                <div class="mb-5 flex items-center justify-between">
                    <span class="text-sm font-medium text-[#526a70]">Materiales de instalación</span>
                    <span class="h-2 w-8 rounded-full bg-[#6d9f43]"></span>
                </div>
                <p class="text-3xl font-semibold text-[#155a91]">{{ number_format($materialCount) }}</p>
                <span class="mt-2 block text-xs text-[#718187] group-hover:text-[#155a91]">Abrir catálogo <span aria-hidden="true">→</span></span>
            </a>
            <a href="{{ route('admin.solar-projects.index') }}" class="group border border-[#dce6e1] bg-white p-5 transition hover:border-[#8ebc72]">
                <div class="mb-5 flex items-center justify-between">
                    <span class="text-sm font-medium text-[#526a70]">Proyectos fotovoltaicos</span>
                    <span class="h-2 w-8 rounded-full bg-[#f3bd35]"></span>
                </div>
                <p class="text-3xl font-semibold text-[#155a91]">{{ number_format($projectCount) }}</p>
                <span class="mt-2 block text-xs text-[#718187] group-hover:text-[#155a91]">Abrir proyectos <span aria-hidden="true">→</span></span>
            </a>
        </div>
    </section>

    <section aria-labelledby="quick-actions" class="border-t border-[#dce6e1] pt-7">
        <h2 id="quick-actions" class="mb-4 text-lg font-semibold text-[#173b49]">Acceso directo</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.appliances.create') }}" class="button-primary inline-flex items-center gap-2 rounded-md px-4 py-2.5 text-sm font-semibold">+ Electrodoméstico</a>
            <a href="{{ route('admin.electrical-equipment.create') }}" class="button-secondary inline-flex items-center gap-2 rounded-md bg-white px-4 py-2.5 text-sm font-semibold">+ Equipo eléctrico</a>
            <a href="{{ route('admin.installation-materials.create') }}" class="button-secondary inline-flex items-center gap-2 rounded-md bg-white px-4 py-2.5 text-sm font-semibold">+ Material</a>
            <a href="{{ route('admin.solar-projects.create') }}" class="button-secondary inline-flex items-center gap-2 rounded-md bg-white px-4 py-2.5 text-sm font-semibold">+ Proyecto solar</a>
        </div>
    </section>
@endsection