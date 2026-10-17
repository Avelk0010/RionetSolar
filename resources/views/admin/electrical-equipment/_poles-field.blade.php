<div class="space-y-1.5">
    <label for="{{ $fieldId }}" class="block text-sm font-medium text-[#294751]">Cantidad de polos <span class="text-[#bd5146]">*</span></label>
    <select id="{{ $fieldId }}" name="poles" required @if($errors->has('poles')) aria-invalid="true" @endif class="field-control block w-full rounded-md px-3 py-2.5 text-sm">
        <option value="">Selecciona</option>
        @foreach([1 => '1 polo (unipolar)', 2 => '2 polos (bipolar)', 3 => '3 polos (tripolar)', 4 => '4 polos (tetrapolar)'] as $value => $label)
            <option value="{{ $value }}" @selected((string) old('poles', $selected) === (string) $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('poles')<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror
</div>
