<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSolarProjectApplianceRequest;
use App\Models\Appliance;
use App\Models\SolarProject;
use App\Models\SolarProjectAppliance;
use App\Services\SolarProjectCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SolarProjectApplianceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(SolarProject $solarProject): View
    {
        $solarProject->load('systemDesign');
        $items = $solarProject->applianceItems()->with('appliance')->orderBy('id')->get();
        $availableAppliances = Appliance::active()
            ->whereNotIn('id', $solarProject->applianceItems()->select('appliance_id'))
            ->orderBy('name')
            ->get();

        return view('admin.solar-projects.loads.index', [
            'project' => $solarProject,
            'items' => $items,
            'availableAppliances' => $availableAppliances,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSolarProjectApplianceRequest $request, SolarProject $solarProject, SolarProjectCalculator $calculator): RedirectResponse
    {
        $data = $request->validated();
        $appliance = Appliance::active()->findOrFail($data['appliance_id']);
        $calculator->addAppliance($solarProject, $appliance, $data);

        return redirect()->route('admin.solar-projects.loads.index', $solarProject)
            ->with('success', 'Electrodoméstico agregado y consumos recalculados.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SolarProject $solarProject, SolarProjectAppliance $item, SolarProjectCalculator $calculator): RedirectResponse
    {
        $item = $solarProject->applianceItems()->findOrFail($item->id);
        $calculator->removeAppliance($item);

        return redirect()->route('admin.solar-projects.loads.index', $solarProject)
            ->with('success', 'Electrodoméstico retirado y consumos recalculados.');
    }
}
