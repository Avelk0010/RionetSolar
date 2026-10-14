@extends('layouts.app')

@section('title', 'Nuevo material')

@section('content')
    <div class="mb-7"><a href="{{ route('admin.installation-materials.index') }}" class="text-sm font-semibold text-[#155a91] hover:underline">← Materiales de instalación</a><p class="mb-2 mt-6 text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Nuevo registro</p><h1 class="text-2xl font-semibold text-[#12384a]">Crear material</h1></div>
    <form method="POST" action="{{ route('admin.installation-materials.store') }}" class="max-w-3xl space-y-7 border-y border-[#dce6e1] bg-white p-5 sm:p-7">@csrf @include('admin.installation-materials._form', ['material' => null])<div class="flex flex-wrap justify-end gap-3 border-t border-[#e8eeea] pt-5"><a href="{{ route('admin.installation-materials.index') }}" class="button-secondary rounded-md bg-white px-4 py-2.5 text-sm font-semibold">Cancelar</a><button class="button-primary rounded-md px-5 py-2.5 text-sm font-semibold">Guardar material</button></div></form>
@endsection