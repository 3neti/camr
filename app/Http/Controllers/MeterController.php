<?php

namespace App\Http\Controllers;

use App\Http\Requests\{StoreMeterRequest, UpdateMeterRequest};
use App\Http\Resources\MeterDataResource;
use App\Models\{Meter, Gateway, Location, Building, Site};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MeterController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Meter::with(['gateway.site', 'location', 'configurationFile'])
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
                $status = strtolower($status);
                if ($status === 'active') {
                    $query->where('status', 'Active');
                } elseif ($status === 'inactive') {
                    $query->where('status', 'Inactive');
                }
            });

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortColumn, ['name', 'type', 'brand', 'customer_name', 'status', 'created_at'])) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->latest();
        }

        $meters = $query->paginate(15)->withQueryString();

        return Inertia::render('meters/Index', [
            'meters' => $meters,
            'gateways' => Gateway::with('site')->get(['id', 'serial_number', 'site_id']),
            'sites' => Site::all(['id', 'code']),
            'filters' => $request->only(['search', 'gateway_id', 'site_id', 'type', 'brand', 'status', 'sort', 'direction']),
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
        $gateway = Gateway::findOrFail($request->gateway_id);
        
        $meter = Meter::create([
            ...$request->validated(),
            'site_id' => $gateway->site_id,
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
            'configurationFile',
            'meterData' => fn ($q) => $q->latest('reading_datetime')->limit(10),
            'loadProfiles' => fn ($q) => $q->latest('reading_datetime')->limit(5),
        ]);

        // Transform meter data with type safety
        $meter->meter_data = MeterDataResource::collection($meter->meterData);

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

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:meters,id',
        ]);

        $count = Meter::whereIn('id', $request->ids)->delete();

        return redirect()->route('meters.index')
            ->with('success', "{$count} meters deleted successfully.");
    }
}
