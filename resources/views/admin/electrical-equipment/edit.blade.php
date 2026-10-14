@extends('layouts.app')

@section('title', 'Editar equipo eléctrico')

@section('content')
    <div class="mb-7"><a href="{{ route('admin.electrical-equipment.show', $equipment) }}" class="text-sm font-semibold text-[#155a91] hover:underline">← Detalle del equipo</a><p class="mb-2 mt-6 text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Editar registro</p><h1 class="text-2xl font-semibold text-[#12384a]">{{ $equipment->name }}</h1></div>
    <form method="POST" action="{{ route('admin.electrical-equipment.update', $equipment) }}" class="space-y-7 border-y border-[#dce6e1] bg-white p-5 sm:p-7">@csrf @method('PUT') @include('admin.electrical-equipment._form', ['equipment' => $equipment])<div class="flex flex-wrap justify-end gap-3 border-t border-[#e8eeea] pt-5"><a href="{{ route('admin.electrical-equipment.show', $equipment) }}" class="button-secondary rounded-md bg-white px-4 py-2.5 text-sm font-semibold">Cancelar</a><button class="button-primary rounded-md px-5 py-2.5 text-sm font-semibold">Guardar cambios</button></div></form>
@endsection