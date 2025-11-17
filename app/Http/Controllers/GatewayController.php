<?php

namespace App\Http\Controllers;

use App\Http\Requests\{StoreGatewayRequest, UpdateGatewayRequest};
use App\Models\{Gateway, Site, Location};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GatewayController extends Controller
{
    public function index(Request $request): Response
    {
        $gateways = Gateway::with(['site', 'location'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('serial_number', 'like', "%{$search}%")
                      ->orWhere('mac_address', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->when($request->site_id, function ($query, $siteId) {
                $query->where('site_id', $siteId);
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'online') {
                    $query->online();
                } elseif ($status === 'offline') {
                    $query->offline();
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('gateways/Index', [
            'gateways' => $gateways,
            'sites' => Site::all(['id', 'code']),
            'filters' => $request->only(['search', 'site_id', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('gateways/Create', [
            'sites' => Site::all(['id', 'code']),
            'locations' => Location::all(['id', 'code', 'description']),
        ]);
    }

    public function store(StoreGatewayRequest $request)
    {
        Gateway::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('gateways.index')
            ->with('success', 'Gateway created successfully.');
    }

    public function show(Gateway $gateway): Response
    {
        $gateway->load([
            'site',
            'location',
            'meters' => fn ($q) => $q->with('location'),
        ]);

        return Inertia::render('gateways/Show', [
            'gateway' => $gateway,
        ]);
    }

    public function edit(Gateway $gateway): Response
    {
        $gateway->load(['site', 'location']);

        return Inertia::render('gateways/Edit', [
            'gateway' => $gateway,
            'sites' => Site::all(['id', 'code']),
            'locations' => Location::all(['id', 'code', 'description']),
        ]);
    }

    public function update(UpdateGatewayRequest $request, Gateway $gateway)
    {
        $gateway->update([
            ...$request->validated(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('gateways.show', $gateway)
            ->with('success', 'Gateway updated successfully.');
    }

    public function destroy(Gateway $gateway)
    {
        $gateway->delete();

        return redirect()->route('gateways.index')
            ->with('success', 'Gateway deleted successfully.');
    }
}
