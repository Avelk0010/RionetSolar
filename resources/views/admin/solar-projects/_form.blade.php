<div class="grid gap-5 sm:grid-cols-2">
    <x-input-field name="name" label="Nombre del proyecto" :value="$project?->name" required />
    <x-input-field name="solar_hours_per_day" label="Horas solares pico por día" type="number" step="0.01" min="0.01" max="24" :value="$project?->solar_hours_per_day" required />
    <x-input-field name="latitude" label="Latitud" type="number" step="0.0000001" min="-90" max="90" :value="$project?->latitude" placeholder="Ej. 4.7110" />
    <x-input-field name="longitude" label="Longitud" type="number" step="0.0000001" min="-180" max="180" :value="$project?->longitude" placeholder="Ej. -74.0721" />
    <div class="sm:col-span-2"><x-input-field name="description" label="Descripción" type="textarea" :value="$project?->description" /></div>
    <div class="sm:col-span-2"><x-input-field name="project_warranty" label="Garantías del proyecto" type="textarea" :rows="8" :value="$project?->project_warranty" required /></div>
</div>