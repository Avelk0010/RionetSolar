<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstallationMaterialRequest;
use App\Http\Requests\Admin\UpdateInstallationMaterialRequest;
use App\Models\InstallationMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstallationMaterialController extends Controller
{
    public function index(Request $request): View
    {
        $materials = InstallationMaterial::query()
            ->when($request->filled('search'), fn (Builder $query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->when($request->query('status') === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.installation-materials.index', compact('materials'));
    }

    public function create(): View
    {
        return view('admin.installation-materials.create');
    }

    public function store(StoreInstallationMaterialRequest $request): RedirectResponse
    {
        $material = InstallationMaterial::query()->create($request->validated());

        return redirect()->route('admin.installation-materials.show', $material)->with('success', 'Material de instalación creado correctamente.');
    }

    public function show(InstallationMaterial $material): View
    {
        return view('admin.installation-materials.show', compact('material'));
    }

    public function edit(InstallationMaterial $material): View
    {
        return view('admin.installation-materials.edit', compact('material'));
    }

    public function update(UpdateInstallationMaterialRequest $request, InstallationMaterial $material): RedirectResponse
    {
        $material->update($request->validated());

        return redirect()->route('admin.installation-materials.show', $material)->with('success', 'Material de instalación actualizado correctamente.');
    }

    public function forceDestroy(InstallationMaterial $material): RedirectResponse
    {
        $material->delete();

        return redirect()->route('admin.installation-materials.index')
            ->with('success', 'Material eliminado definitivamente.');
    }

    public function destroy(InstallationMaterial $material): RedirectResponse
    {
        $material->update(['is_active' => false]);

        return redirect()->route('admin.installation-materials.index')->with('success', 'Material desactivado. Su historial se conserva.');
    }
}
