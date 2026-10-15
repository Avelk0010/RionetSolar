<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('admin.solar-projects.index') }}" class="text-sm font-semibold text-[#155a91] hover:underline">← Proyectos solares</a>
        <p class="mb-2 mt-5 text-xs font-bold uppercase tracking-[0.14em] text-[#5f963f]">Proyecto fotovoltaico</p>
        <h1 class="text-2xl font-semibold text-[#12384a]">{{ $project->name }}</h1>
        <p class="mt-1 text-sm text-[#687b80]">{{ $project->description ?: 'Sin descripción' }}</p>
    </div>
    <a href="{{ route('admin.solar-projects.edit', $project) }}" class="button-secondary rounded-md bg-white px-4 py-2.5 text-sm font-semibold">Editar proyecto</a>
</div>
<nav class="mb-7 flex flex-wrap gap-2 border-b border-[#dce6e1] pb-3" aria-label="Submódulos del proyecto">
    <a href="{{ route('admin.solar-projects.loads.index', $project) }}" @if($activeModule === 'loads') aria-current="page" @endif class="{{ $activeModule === 'loads' ? 'button-primary' : 'button-secondary bg-white' }} rounded-md px-4 py-2 text-sm font-semibold">Cargas eléctricas</a>
    <a href="{{ route('admin.solar-projects.system-design.show', $project) }}" @if($activeModule === 'design') aria-current="page" @endif class="{{ $activeModule === 'design' ? 'button-primary' : 'button-secondary bg-white' }} rounded-md px-4 py-2 text-sm font-semibold">Dimensionamiento</a>
</nav>