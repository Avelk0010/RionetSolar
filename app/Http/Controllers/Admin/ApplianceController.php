<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreApplianceRequest;
use App\Http\Requests\Admin\UpdateApplianceRequest;
use App\Models\Appliance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplianceController extends Controller
{
    public function index(Request $request): View
    {
        $appliances = Appliance::query()
            ->when($request->filled('search'), fn (Builder $query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->when($request->filled('voltage'), fn (Builder $query) => $query->where('voltage', $request->integer('voltage')))
            ->when($request->query('status') === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.appliances.index', compact('appliances'));
    }

    public function create(): View
    {
        return view('admin.appliances.create');
    }

    public function store(StoreApplianceRequest $request): RedirectResponse
    {
        $appliance = Appliance::query()->create($request->validated());

        return redirect()->route('admin.appliances.show', $appliance)->with('success', 'Electrodoméstico creado correctamente.');
    }

    public function show(Appliance $appliance): View
    {
        return view('admin.appliances.show', compact('appliance'));
    }

    public function edit(Appliance $appliance): View
    {
        return view('admin.appliances.edit', compact('appliance'));
    }

    public function update(UpdateApplianceRequest $request, Appliance $appliance): RedirectResponse
    {
        $appliance->update($request->validated());

        return redirect()->route('admin.appliances.show', $appliance)->with('success', 'Electrodoméstico actualizado correctamente.');
    }

    public function destroy(Appliance $appliance): RedirectResponse
    {
        $appliance->update(['is_active' => false]);

        return redirect()->route('admin.appliances.index')->with('success', 'Electrodoméstico desactivado. Su historial se conserva.');
    }
}
