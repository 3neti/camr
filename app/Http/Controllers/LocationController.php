<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Site;
use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Location::query()->with(['site', 'building']);

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Filter by site
        if ($request->filled('site_id') && $request->site_id !== 'all') {
            $query->where('site_id', $request->site_id);
        }

        // Filter by building
        if ($request->filled('building_id') && $request->building_id !== 'all') {
            $query->where('building_id', $request->building_id);
        }

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortColumn, ['code', 'description', 'created_at'])) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->latest();
        }

        $locations = $query->paginate(15)->withQueryString();

        return Inertia::render('locations/Index', [
            'locations' => $locations,
            'sites' => Site::orderBy('code')->get(['id', 'code']),
            'buildings' => Building::orderBy('code')->get(['id', 'code', 'site_id']),
            'filters' => $request->only(['search', 'site_id', 'building_id', 'sort', 'direction']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('locations/Create', [
            'sites' => Site::orderBy('code')->get(['id', 'code']),
            'buildings' => Building::orderBy('code')->get(['id', 'code', 'site_id', 'description']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'building_id' => 'nullable|exists:buildings,id',
            'code' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        Location::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('locations.index')
            ->with('success', 'Location created successfully.');
    }

    public function show(Location $location): Response
    {
        $location->load([
            'site',
            'building',
            'gateways',
            'meters' => fn ($q) => $q->with('gateway'),
        ]);

        return Inertia::render('locations/Show', [
            'location' => $location,
        ]);
    }

    public function edit(Location $location): Response
    {
        return Inertia::render('locations/Edit', [
            'location' => $location,
            'sites' => Site::orderBy('code')->get(['id', 'code']),
            'buildings' => Building::orderBy('code')->get(['id', 'code', 'site_id', 'description']),
        ]);
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'building_id' => 'nullable|exists:buildings,id',
            'code' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $location->update([
            ...$validated,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('locations.show', $location)
            ->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location)
    {
        // Check if any gateways or meters are using this location
        if ($location->gateways()->count() > 0 || $location->meters()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete location that has gateways or meters.');
        }

        $location->delete();

        return redirect()->route('locations.index')
            ->with('success', 'Location deleted successfully.');
    }
}
