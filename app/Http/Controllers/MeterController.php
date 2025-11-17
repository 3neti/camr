<?php

namespace App\Http\Controllers;

use App\Http\Requests\{StoreMeterRequest, UpdateMeterRequest};
use App\Models\{Meter, Gateway, Location, Building, Site};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MeterController extends Controller
{
    public function index(Request $request): Response
    {
        $meters = Meter::with(['gateway.site', 'location'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->when($request->gateway_id, function ($query, $gatewayId) {
                $query->where('gateway_id', $gatewayId);
            })
            ->when($request->site_id, function ($query, $siteId) {
                $query->where('site_id', $siteId);
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->brand, function ($query, $brand) {
                $query->where('brand', $brand);
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'active') {
                    $query->active();
                } elseif ($status === 'inactive') {
                    $query->inactive();
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('meters/Index', [
            'meters' => $meters,
            'gateways' => Gateway::with('site')->get(['id', 'serial_number', 'site_id']),
            'sites' => Site::all(['id', 'code']),
            'filters' => $request->only(['search', 'gateway_id', 'site_id', 'type', 'brand', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('meters/Create', [
            'gateways' => Gateway::with('site')->get(['id', 'serial_number', 'site_id']),
            'locations' => Location::all(['id', 'code', 'description']),
            'buildings' => Building::all(['id', 'code', 'description']),
        ]);
    }

    public function store(StoreMeterRequest $request)
    {
        $meter = Meter::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('meters.index')
            ->with('success', 'Meter created successfully.');
    }

    public function show(Meter $meter): Response
    {
        $meter->load([
            'gateway.site',
            'location',
            'building',
            'meterData' => fn ($q) => $q->latest()->limit(10),
            'loadProfiles' => fn ($q) => $q->latest()->limit(5),
        ]);

        return Inertia::render('meters/Show', [
            'meter' => $meter,
        ]);
    }

    public function edit(Meter $meter): Response
    {
        $meter->load(['gateway', 'location', 'building']);

        return Inertia::render('meters/Edit', [
            'meter' => $meter,
            'gateways' => Gateway::with('site')->get(['id', 'serial_number', 'site_id']),
            'locations' => Location::all(['id', 'code', 'description']),
            'buildings' => Building::all(['id', 'code', 'description']),
        ]);
    }

    public function update(UpdateMeterRequest $request, Meter $meter)
    {
        $meter->update([
            ...$request->validated(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('meters.show', $meter)
            ->with('success', 'Meter updated successfully.');
    }

    public function destroy(Meter $meter)
    {
        $meter->delete();

        return redirect()->route('meters.index')
            ->with('success', 'Meter deleted successfully.');
    }
}
