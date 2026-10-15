<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSolarProjectSystemDesignRequest;
use App\Models\ElectricalEquipment;
use App\Models\SolarProject;
use App\Services\SolarProjectCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SolarProjectSystemDesignController extends Controller
{
    public function show(SolarProject $solarProject): View
    {
        $solarProject->load('systemDesign.solarPanel', 'systemDesign.inverter', 'systemDesign.battery');
        $design = $solarProject->systemDesign;

        $panels = $this->equipmentOptions('paneles-solares', $design?->solar_panel_equipment_id, 'solarPanelSpec');
        $inverters = $this->equipmentOptions('inversores', $design?->inverter_equipment_id, 'inverterSpec');
        $batteries = $this->equipmentOptions('baterias', $design?->battery_equipment_id, 'batterySpec');

        return view('admin.solar-projects.system-design.show', [
            'project' => $solarProject,
            'design' => $design,
            'panels' => $panels,
            'inverters' => $inverters,
            'batteries' => $batteries,
        ]);
    }

    public function update(
        SaveSolarProjectSystemDesignRequest $request,
        SolarProject $solarProject,
        SolarProjectCalculator $calculator,
    ): RedirectResponse {
        $calculator->saveSystemDesign($solarProject, $request->validated());

        return redirect()->route('admin.solar-projects.system-design.show', $solarProject)
            ->with('success', 'Dimensionamiento guardado y cantidades calculadas.');
    }

    private function equipmentOptions(string $categorySlug, ?int $selectedId, string $specificationRelation)
    {
        return ElectricalEquipment::query()
            ->whereHas('category', fn (Builder $query) => $query->where('slug', $categorySlug))
            ->where(function (Builder $query) use ($selectedId): void {
                $query->where('is_active', true);

                if ($selectedId !== null) {
                    $query->orWhereKey($selectedId);
                }
            })
            ->with($specificationRelation)
            ->orderBy('name')
            ->get();
    }
}
