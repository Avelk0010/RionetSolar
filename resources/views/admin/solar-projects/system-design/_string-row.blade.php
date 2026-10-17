<div data-row class="flex items-center gap-2">
    <span data-row-label class="w-14 shrink-0 text-xs font-semibold text-[#597078]">Fila</span>
    <input type="number" min="1" step="1" inputmode="numeric" name="rows[{{ $stringNumber }}][]" value="{{ $value }}" aria-label="Paneles de la fila del string {{ $stringNumber }}" class="field-control w-full rounded-md px-3 py-1.5 text-sm" placeholder="Paneles">
    <button type="button" data-remove-row class="rounded-md px-2 py-1 text-sm font-semibold text-[#a5443a] hover:bg-[#fff0ed]" aria-label="Quitar fila">&times;</button>
</div>
