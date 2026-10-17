<td class="px-3 py-2">
    @if($material)
        <a href="{{ route('admin.installation-materials.show', $material) }}" class="font-semibold text-[#155a91] hover:underline">{{ $material->name }}</a>
        <span class="block text-xs text-[#718187]">$ {{ number_format((float) $material->price, 2, ',', '.') }} COP por {{ $material->unit }}</span>
    @else
        <span class="text-xs text-[#6e5a22]">No hay material registrado con esta categoría.</span>
    @endif
</td>
