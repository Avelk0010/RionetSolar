<tr data-quote-row data-line-key="{{ $row['key'] ?? '' }}" data-unit-price="{{ $row['unit_price'] }}" data-base-quantity="{{ $row['base'] }}" class="align-top">
    <td class="px-3 py-2 text-xs text-[#718187]" data-row-number></td>
    <td class="min-w-[240px] px-3 py-2">
        <input type="hidden" name="lines[{{ $index }}][line_key]" value="{{ $row['key'] }}">
        @if($row['manual'])
            <select name="lines[{{ $index }}][item]" data-item-select aria-label="Ítem" class="field-control w-full rounded-md px-2 py-1.5 text-sm">
                <option value="">Selecciona un ítem</option>
                @foreach($options as $group => $items)
                    <optgroup label="{{ $group }}">
                        @foreach($items as $value => $item)
                            <option value="{{ $value }}" data-price="{{ $item['price'] }}" @selected($row['item'] === $value)>{{ $item['name'] }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        @else
            <input type="hidden" name="lines[{{ $index }}][item]" value="{{ $row['item'] }}">
            <span class="font-semibold text-[#173b49]">{{ $row['name'] }}</span>
            @if($row['concept'])<span class="block text-xs text-[#718187]">{{ $row['concept'] }}</span>@endif
        @endif
        @foreach(['item', 'adjustment', 'tax_percent', 'discount_percent'] as $field)
            @error("lines.$index.$field")<p class="mt-1 text-xs text-[#b43f35]">{{ $message }}</p>@enderror
        @endforeach
    </td>
    <td class="px-3 py-2 text-right" data-base-cell>{{ $row['base'] }}</td>
    <td class="px-3 py-2">
        <input type="number" step="1" name="lines[{{ $index }}][adjustment]" value="{{ $row['adjustment'] }}" data-adjustment @if($row['key'] === 'panel') data-panel-adjustment @endif @readonly(! $row['editable']) aria-label="Ajuste de cantidad (+/-)" class="field-control w-20 rounded-md px-2 py-1.5 text-right text-sm read-only:bg-[#f4f6f4]">
        @if($row['key'] === 'panel')<span data-panel-balance-note class="mt-1 block max-w-44 text-[10px] leading-4 text-[#6e5a22]" hidden></span>@endif
    </td>
    <td class="px-3 py-2 text-right" data-unit-price-cell></td>
    <td class="px-3 py-2"><input type="number" step="0.01" min="0" max="100" name="lines[{{ $index }}][tax_percent]" value="{{ $row['tax'] }}" data-tax aria-label="Impuesto (%)" class="field-control w-20 rounded-md px-2 py-1.5 text-right text-sm"></td>
    <td class="px-3 py-2"><input type="number" step="0.01" min="0" max="100" name="lines[{{ $index }}][discount_percent]" value="{{ $row['discount'] }}" data-discount aria-label="Descuento (%)" class="field-control w-20 rounded-md px-2 py-1.5 text-right text-sm"></td>
    <td class="px-3 py-2 text-right font-semibold text-[#173b49]" data-line-total></td>
    <td class="px-3 py-2">@if($row['removable'])<button type="button" data-remove-quote-row class="rounded-md px-2 py-1 text-sm font-semibold text-[#a5443a] hover:bg-[#fff0ed]" aria-label="Quitar fila">&times;</button>@endif</td>
</tr>
