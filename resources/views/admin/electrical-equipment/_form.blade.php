<div class="grid gap-5 sm:grid-cols-2">
    <div class="space-y-1.5">
        <label for="electrical_equipment_category_id" class="block text-sm font-medium text-[#294751]">Categoría <span class="text-[#bd5146]">*</span></label>
        @if($equipment)
            <input type="hidden" name="electrical_equipment_category_id" value="{{ $equipment->electrical_equipment_category_id }}">
        @endif
        <select id="electrical_equipment_category_id" name="electrical_equipment_category_id" data-category-select required @if($equipment) disabled @endif @if($errors->has('electrical_equipment_category_id')) aria-invalid="true" @endif class="field-control block w-full rounded-md px-3 py-2.5 text-sm">
            <option value="">Selecciona una categoría</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" data-slug="{{ $category->slug }}" @selected((string) old('electrical_equipment_category_id', $equipment?->electrical_equipment_category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('electrical_equipment_category_id')<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror
        @if($equipment)<p class="text-xs text-[#718187]">La categoría queda fija para conservar la ficha técnica compatible.</p>@endif
    </div>
    <x-input-field name="name" label="Nombre comercial" :value="$equipment?->name" required />
    <x-input-field name="price" label="Precio de venta (COP)" type="number" step="0.01" min="0" :value="$equipment?->price" required />
    <x-input-field name="brand" label="Marca" :value="$equipment?->brand" />
    <x-input-field name="model" label="Modelo / referencia" :value="$equipment?->model" />
    <x-input-field name="sku" label="SKU / código interno" :value="$equipment?->sku" />
    <div class="flex items-center gap-3 self-end rounded-md border border-[#dce6e1] bg-white px-4 py-3">
        <input type="hidden" name="is_active" value="0">
        <input id="is_active" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $equipment?->is_active ?? true)) class="h-4 w-4 accent-[#5f963f]">
        <label for="is_active" class="text-sm font-medium text-[#294751]">Disponible para nuevos proyectos</label>
    </div>
    <div class="sm:col-span-2"><x-input-field name="description" label="Descripción" type="textarea" :value="$equipment?->description" /></div>
</div>

<div class="border-t border-[#dce6e1] pt-6">
    <div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Ficha técnica</p><h2 class="mt-1 text-lg font-semibold text-[#173b49]">Especificaciones del equipo</h2></div>
    <fieldset data-spec-group="inversores" class="grid gap-5 sm:grid-cols-2" hidden>
        <legend class="sr-only">Especificaciones del inversor</legend>
        <x-input-field name="mppt_count" label="Cantidad de MPPT" type="number" step="1" min="1" :value="$equipment?->inverterSpec?->mppt_count" required />
        <x-input-field name="mppt_max_voltage_v" label="Voltaje máximo MPPT (V)" type="number" step="any" min="0.001" :value="$equipment?->inverterSpec?->mppt_max_voltage_v" required />
        <x-input-field name="mppt_max_current_a" label="Corriente máxima MPPT (A)" type="number" step="any" min="0.001" :value="$equipment?->inverterSpec?->mppt_max_current_a" required />
        <x-input-field name="mppt_max_power_w" label="Potencia máxima MPPT (W)" type="number" step="0.01" min="0.01" :value="$equipment?->inverterSpec?->mppt_max_power_w" required />
        <x-input-field name="battery_charge_voltage_v" label="Voltaje de carga de batería (V)" type="number" step="any" min="0.001" :value="$equipment?->inverterSpec?->battery_charge_voltage_v" required />
        <div class="space-y-1.5"><label for="ac_input_voltage_v" class="block text-sm font-medium text-[#294751]">Voltaje de entrada AC (V) *</label><select id="ac_input_voltage_v" name="ac_input_voltage_v" class="field-control block w-full rounded-md px-3 py-2.5 text-sm"><option value="">Selecciona</option><option value="110" @selected((string) old('ac_input_voltage_v', $equipment?->inverterSpec?->ac_input_voltage_v) === '110')>110 V</option><option value="220" @selected((string) old('ac_input_voltage_v', $equipment?->inverterSpec?->ac_input_voltage_v) === '220')>220 V</option></select>@error('ac_input_voltage_v')<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror</div>
        <div class="space-y-1.5"><label for="ac_output_voltage_v" class="block text-sm font-medium text-[#294751]">Voltaje de salida AC (V) *</label><select id="ac_output_voltage_v" name="ac_output_voltage_v" class="field-control block w-full rounded-md px-3 py-2.5 text-sm"><option value="">Selecciona</option><option value="110" @selected((string) old('ac_output_voltage_v', $equipment?->inverterSpec?->ac_output_voltage_v) === '110')>110 V</option><option value="220" @selected((string) old('ac_output_voltage_v', $equipment?->inverterSpec?->ac_output_voltage_v) === '220')>220 V</option></select>@error('ac_output_voltage_v')<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror</div>
        <x-input-field name="max_output_current_a" label="Corriente máxima de salida AC (A)" type="number" step="any" min="0.001" :value="$equipment?->inverterSpec?->max_output_current_a" required />
        <x-input-field name="max_output_power_w" label="Potencia máxima de salida (W)" type="number" step="0.01" min="0.01" :value="$equipment?->inverterSpec?->max_output_power_w" required />
    </fieldset>

    <fieldset data-spec-group="paneles-solares" class="grid gap-5 sm:grid-cols-2" hidden>
        <legend class="sr-only">Especificaciones del panel solar</legend>
        <x-input-field name="max_power_w" label="Potencia máxima (W)" type="number" step="0.01" min="0.01" :value="$equipment?->solarPanelSpec?->max_power_w" required />
        <x-input-field name="max_power_voltage_v" label="Voltaje de máxima potencia Vmp (V)" type="number" step="any" min="0.001" :value="$equipment?->solarPanelSpec?->max_power_voltage_v" required />
        <x-input-field name="max_power_current_a" label="Corriente de máxima potencia Imp (A)" type="number" step="any" min="0.001" :value="$equipment?->solarPanelSpec?->max_power_current_a" required />
        <x-input-field name="open_circuit_voltage_v" label="Voltaje de circuito abierto Voc (V)" type="number" step="any" min="0.001" :value="$equipment?->solarPanelSpec?->open_circuit_voltage_v" required />
        <x-input-field name="short_circuit_current_a" label="Corriente de cortocircuito Isc (A)" type="number" step="any" min="0.001" :value="$equipment?->solarPanelSpec?->short_circuit_current_a" required />
        <x-input-field name="max_system_voltage_v" label="Voltaje máximo del sistema (V)" type="number" step="any" min="0.001" :value="$equipment?->solarPanelSpec?->max_system_voltage_v" required />
        <x-input-field name="length_mm" label="Largo (mm)" type="number" step="0.01" min="0.01" :value="$equipment?->solarPanelSpec?->length_mm" required />
        <x-input-field name="width_mm" label="Ancho (mm)" type="number" step="0.01" min="0.01" :value="$equipment?->solarPanelSpec?->width_mm" required />
        <x-input-field name="height_mm" label="Alto (mm)" type="number" step="0.01" min="0.01" :value="$equipment?->solarPanelSpec?->height_mm" required />
    </fieldset>

    <fieldset data-spec-group="baterias" class="grid gap-5 sm:grid-cols-2" hidden>
        <legend class="sr-only">Especificaciones de la batería</legend>
        <x-input-field name="capacity_ah" label="Capacidad (Ah)" type="number" step="any" min="0.001" :value="$equipment?->batterySpec?->capacity_ah" required />
        <x-input-field name="voltage_v" label="Voltaje nominal (V)" type="number" step="any" min="0.001" :value="$equipment?->batterySpec?->voltage_v" required />
        <x-input-field name="nominal_power_w" label="Potencia nominal (W), si está especificada" type="number" step="0.01" min="0.01" :value="$equipment?->batterySpec?->nominal_power_w" />
    </fieldset>

    <fieldset data-spec-group="breakers-dc" class="grid gap-5 sm:grid-cols-2" hidden>
        <legend class="sr-only">Especificaciones del breaker DC</legend>
        <x-input-field name="min_operating_voltage_v" label="Voltaje mínimo de operación (V)" type="number" step="any" min="0.001" :value="$equipment?->dcBreakerSpec?->min_operating_voltage_v" required />
        <x-input-field name="max_operating_voltage_v" label="Voltaje máximo de operación (V)" type="number" step="any" min="0.001" :value="$equipment?->dcBreakerSpec?->max_operating_voltage_v" required />
        <x-input-field name="rated_current_a" label="Corriente nominal (A)" type="number" step="any" min="0.001" :value="$equipment?->dcBreakerSpec?->rated_current_a" required />
    </fieldset>

    <fieldset data-spec-group="breakers-ac" class="grid gap-5 sm:grid-cols-2" hidden>
        <legend class="sr-only">Especificaciones del breaker AC</legend>
        <div class="space-y-1.5"><label for="operating_voltage_v" class="block text-sm font-medium text-[#294751]">Voltaje de operación (V) *</label><select id="operating_voltage_v" name="operating_voltage_v" class="field-control block w-full rounded-md px-3 py-2.5 text-sm"><option value="">Selecciona</option><option value="110" @selected((string) old('operating_voltage_v', $equipment?->acBreakerSpec?->operating_voltage_v) === '110')>110 V</option><option value="220" @selected((string) old('operating_voltage_v', $equipment?->acBreakerSpec?->operating_voltage_v) === '220')>220 V</option></select>@error('operating_voltage_v')<p class="text-xs text-[#b43f35]">{{ $message }}</p>@enderror</div>
        <x-input-field name="rated_current_a" label="Corriente nominal (A)" type="number" step="any" min="0.001" :value="$equipment?->acBreakerSpec?->rated_current_a" required />
    </fieldset>

    <fieldset data-spec-group="cable-solar" class="grid gap-5 sm:grid-cols-2" hidden>
        <legend class="sr-only">Especificaciones del cable solar</legend>
        <x-input-field name="awg_gauge" label="Calibre AWG (por ejemplo 4/0, 1/0 o 10)" :value="$equipment?->solarCableSpec?->awg_gauge" required />
        <x-input-field name="diameter_mm" label="Diámetro (mm)" type="number" step="any" min="0.001" :value="$equipment?->solarCableSpec?->diameter_mm" required />
        <x-input-field name="nominal_voltage_dc_v" label="Voltaje nominal DC (V)" type="number" step="any" min="0.001" :value="$equipment?->solarCableSpec?->nominal_voltage_dc_v" required />
    </fieldset>
    @error('electrical_equipment_category_id')<p class="mt-3 text-xs text-[#b43f35]">{{ $message }}</p>@enderror
</div>