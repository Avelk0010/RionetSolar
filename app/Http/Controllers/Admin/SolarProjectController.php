<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSolarProjectRequest;
use App\Http\Requests\Admin\UpdateSolarProjectRequest;
use App\Models\SolarProject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SolarProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $projects = SolarProject::query()
            ->withCount('applianceItems')
            ->when($request->filled('search'), fn (Builder $query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.solar-projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.solar-projects.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSolarProjectRequest $request): RedirectResponse
    {
        $project = SolarProject::query()->create($request->validated());

        return redirect()->route('admin.solar-projects.show', $project)
            ->with('success', 'Proyecto creado. Agrega sus cargas para comenzar.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SolarProject $solarProject): View
    {
        $solarProject->load('systemDesign.solarPanel', 'systemDesign.inverter', 'systemDesign.battery');

        return view('admin.solar-projects.show', ['project' => $solarProject]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SolarProject $solarProject): View
    {
        return view('admin.solar-projects.edit', ['project' => $solarProject]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSolarProjectRequest $request, SolarProject $solarProject): RedirectResponse
    {
        $solarProject->update($request->validated());

        return redirect()->route('admin.solar-projects.show', $solarProject)
            ->with('success', 'Datos del proyecto actualizados.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SolarProject $solarProject): RedirectResponse
    {
        $solarProject->delete();

        return redirect()->route('admin.solar-projects.index')
            ->with('success', 'Proyecto eliminado con sus cargas y configuración.');
    }
}
