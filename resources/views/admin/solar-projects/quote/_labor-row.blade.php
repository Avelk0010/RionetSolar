<tr data-quote-row data-labor-row data-base-quantity="1" class="align-top bg-[#fbfdf9]">
    <td class="px-3 py-2 text-xs text-[#718187]" data-row-number></td>
    <td class="min-w-[240px] px-3 py-2">
        <span class="font-semibold text-[#173b49]">Mano de obra</span>
        <span class="block text-xs text-[#718187]">Servicio · cantidad fija de 1</span>
        @foreach(['unit_price', 'tax_percent', 'discount_percent'] as $field)
            @error("labor.$field")<p class="mt-1 text-xs text-[#b43f35]">{{ $message }}</p>@enderror
        @endforeach
    </td>
    <td class="px-3 py-2 text-right">1</td>
    <td class="px-3 py-2 text-center text-xs text-[#718187]" aria-label="Sin ajuste">—</td>
    <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="labor[unit_price]" value="{{ old('labor.unit_price', 0) }}" data-unit-input aria-label="Valor de la mano de obra (COP)" class="field-control w-36 rounded-md px-2 py-1.5 text-right text-sm"></td>
    <td class="px-3 py-2"><input type="number" step="0.01" min="0" max="100" name="labor[tax_percent]" value="{{ old('labor.tax_percent', 0) }}" data-tax aria-label="Impuesto de la mano de obra (%)" class="field-control w-20 rounded-md px-2 py-1.5 text-right text-sm"></td>
    <td class="px-3 py-2"><input type="number" step="0.01" min="0" max="100" name="labor[discount_percent]" value="{{ old('labor.discount_percent', 0) }}" data-discount aria-label="Descuento de la mano de obra (%)" class="field-control w-20 rounded-md px-2 py-1.5 text-right text-sm"></td>
    <td class="px-3 py-2 text-right font-semibold text-[#173b49]" data-line-total></td>
    <td class="px-3 py-2"></td>
</tr>
