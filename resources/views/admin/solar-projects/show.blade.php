@extends('layouts.app')

@section('title', $project->name)

@section('content')
    @include('admin.solar-projects._workspace-header', ['activeModule' => 'loads'])
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="border border-[#dce6e1] bg-white p-4"><p class="text-xs text-[#718187]">Horas solares pico</p><p class="mt-1 text-lg font-semibold text-[#155a91]">{{ number_format((float) $project->solar_hours_per_day, 2, ',', '.') }} h/día</p></div>
        <div class="border border-[#dce6e1] bg-white p-4"><p class="text-xs text-[#718187]">Consumo total</p><p class="mt-1 text-lg font-semibold text-[#155a91]">{{ number_format((float) $project->total_consumption_wh_per_day, 2, ',', '.') }} Wh/día</p></div>
        <div class="border border-[#dce6e1] bg-white p-4"><p class="text-xs text-[#718187]">Consumo nocturno</p><p class="mt-1 text-lg font-semibold text-[#155a91]">{{ number_format((float) $project->night_consumption_wh_per_day, 2, ',', '.') }} Wh/día</p></div>
        <div class="border border-[#dce6e1] bg-white p-4"><p class="text-xs text-[#718187]">Potencia pico</p><p class="mt-1 text-lg font-semibold text-[#155a91]">{{ $project->maximum_hourly_power_w !== null ? number_format((float) $project->maximum_hourly_power_w, 2, ',', '.').' W' : 'Pendiente' }}</p></div>
    </section>
    <p class="mt-5 text-sm text-[#687b80]">Coordenadas: {{ $project->latitude !== null ? number_format((float) $project->latitude, 7, ',', '.') : '—' }}, {{ $project->longitude !== null ? number_format((float) $project->longitude, 7, ',', '.') : '—' }}</p>
@endsection