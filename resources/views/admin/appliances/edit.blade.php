@extends('layouts.app')

@section('title', 'Editar electrodoméstico')

@section('content')
    <div class="mb-7"><a href="{{ route('admin.appliances.show', $appliance) }}" class="text-sm font-semibold text-[#155a91] hover:underline">← Detalle del electrodoméstico</a><p class="mb-2 mt-6 text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Editar registro</p><h1 class="text-2xl font-semibold text-[#12384a]">{{ $appliance->name }}</h1></div>
    <form method="POST" action="{{ route('admin.appliances.update', $appliance) }}" class="max-w-3xl space-y-7 border-y border-[#dce6e1] bg-white p-5 sm:p-7">@csrf @method('PUT') @include('admin.appliances._form', ['appliance' => $appliance])<div class="flex flex-wrap justify-end gap-3 border-t border-[#e8eeea] pt-5"><a href="{{ route('admin.appliances.show', $appliance) }}" class="button-secondary rounded-md bg-white px-4 py-2.5 text-sm font-semibold">Cancelar</a><button class="button-primary rounded-md px-5 py-2.5 text-sm font-semibold">Guardar cambios</button></div></form>
@endsection