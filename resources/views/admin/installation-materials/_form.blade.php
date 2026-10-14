<div class="grid gap-5 sm:grid-cols-2">
    <x-input-field name="name" label="Nombre del material" :value="$material?->name" required />
    <x-input-field name="unit" label="Unidad de comercialización" :value="$material?->unit" placeholder="Unidad, metro, juego, paquete" required />
    <x-input-field name="price" label="Precio unitario (COP)" type="number" step="0.01" min="0" :value="$material?->price" required />
    <x-input-field name="sku" label="SKU / código interno" :value="$material?->sku" />
    <div class="flex items-center gap-3 self-end rounded-md border border-[#dce6e1] bg-white px-4 py-3"><input type="hidden" name="is_active" value="0"><input id="is_active" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $material?->is_active ?? true)) class="h-4 w-4 accent-[#5f963f]"><label for="is_active" class="text-sm font-medium text-[#294751]">Disponible en el catálogo</label></div>
    <div class="sm:col-span-2"><x-input-field name="description" label="Descripción" type="textarea" :value="$material?->description" /></div>
</div>