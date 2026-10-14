<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreElectricalEquipmentRequest;
use App\Http\Requests\Admin\UpdateElectricalEquipmentRequest;
use App\Models\ElectricalEquipment;
use App\Models\ElectricalEquipmentCategory;
use App\Services\ElectricalEquipmentCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ElectricalEquipmentController extends Controller
{
    public function __construct(private ElectricalEquipmentCatalog $catalog) {}

    public function index(Request $request): View
    {
        $equipment = ElectricalEquipment::query()
            ->with('category')
            ->when($request->filled('search'), fn (Builder $query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->when($request->filled('category'), fn (Builder $query) => $query->where('electrical_equipment_category_id', $request->integer('category')))
            ->when($request->query('status') === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();
        $categories = ElectricalEquipmentCategory::query()->orderBy('name')->get();

        return view('admin.electrical-equipment.index', compact('equipment', 'categories'));
    }

    public function create(): View
    {
        $categories = ElectricalEquipmentCategory::query()->orderBy('name')->get();

        return view('admin.electrical-equipment.create', compact('categories'));
    }

    public function store(StoreElectricalEquipmentRequest $request): RedirectResponse
    {
        $equipment = $this->catalog->create($request->validated());

        return redirect()->route('admin.electrical-equipment.show', $equipment)->with('success', 'Equipo eléctrico creado con su ficha técnica.');
    }

    public function show(ElectricalEquipment $equipment): View
    {
        $equipment->load('category', 'inverterSpec', 'solarPanelSpec', 'batterySpec', 'dcBreakerSpec', 'acBreakerSpec', 'solarCableSpec');

        return view('admin.electrical-equipment.show', compact('equipment'));
    }

    public function edit(ElectricalEquipment $equipment): View
    {
        $equipment->load('category', 'inverterSpec', 'solarPanelSpec', 'batterySpec', 'dcBreakerSpec', 'acBreakerSpec', 'solarCableSpec');
        $categories = ElectricalEquipmentCategory::query()->orderBy('name')->get();

        return view('admin.electrical-equipment.edit', compact('equipment', 'categories'));
    }

    public function update(UpdateElectricalEquipmentRequest $request, ElectricalEquipment $equipment): RedirectResponse
    {
        $this->catalog->update($equipment, $request->validated());

        return redirect()->route('admin.electrical-equipment.show', $equipment)->with('success', 'Equipo y ficha técnica actualizados correctamente.');
    }

    public function destroy(ElectricalEquipment $equipment): RedirectResponse
    {
        $equipment->update(['is_active' => false]);

        return redirect()->route('admin.electrical-equipment.index')->with('success', 'Equipo desactivado. Su ficha y referencias se conservan.');
    }
}
