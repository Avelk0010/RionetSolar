<div class="grid gap-5 sm:grid-cols-2">
    <x-input-field name="name" label="Nombre del electrodoméstico" :value="$appliance?->name" required />
    <div class="space-y-1.5">
        <label for="voltage" class="block text-sm font-medium text-[#294751]">Voltaje nominal (V) <span class="text-[#bd5146]">*</span></label>
        <select id="voltage" name="voltage" required @if($errors->has('voltage')) aria-invalid="true" @endif class="field-control block w-full rounded-md px-3 py-2.5 text-sm">
            <option value="">Selecciona el voltaje</option>
            <option value="110" @selected((string) old('voltage', $appliance?->voltage) === '110')>110 V</option>
            <option value="220" @selected((string) old('voltage', $appliance?->voltage) === '220')>220 V</option>
        </select>
        @error('voltage')<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror
    </div>
    <x-input-field name="power_w" label="Potencia nominal (W)" type="number" step="0.01" min="0.01" :value="$appliance?->power_w" required />
    <div class="flex items-center gap-3 self-end rounded-md border border-[#dce6e1] bg-white px-4 py-3">
        <input type="hidden" name="is_active" value="0">
        <input id="is_active" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $appliance?->is_active ?? true)) class="h-4 w-4 accent-[#5f963f]">
        <label for="is_active" class="text-sm font-medium text-[#294751]">Disponible en el catálogo</label>
    </div>
    <div class="sm:col-span-2">
        <x-input-field name="description" label="Descripción" type="textarea" :value="$appliance?->description" />
    </div>
</div>