<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Site;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuildingController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Building::query()->with('site');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('site_id') && $request->site_id !== 'all') {
            $query->where('site_id', $request->site_id);
        }

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortColumn, ['code', 'description', 'created_at'])) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->latest();
        }

        $buildings = $query->paginate(15)->withQueryString();

        return Inertia::render('buildings/Index', [
            'buildings' => $buildings,
            'sites' => Site::orderBy('code')->get(['id', 'code']),
            'filters' => $request->only(['search', 'site_id', 'sort', 'direction']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('buildings/Create', [
            'sites' => Site::orderBy('code')->get(['id', 'code']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'code' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Building::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('buildings.index')
            ->with('success', 'Building created successfully.');
    }

    public function show(Building $building): Response
    {
        $building->load(['site', 'locations', 'meters']);

        return Inertia::render('buildings/Show', [
            'building' => $building,
        ]);
    }

    public function edit(Building $building): Response
    {
        return Inertia::render('buildings/Edit', [
            'building' => $building,
            'sites' => Site::orderBy('code')->get(['id', 'code']),
        ]);
    }

    public function update(Request $request, Building $building)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'code' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $building->update([
            ...$validated,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('buildings.show', $building)
            ->with('success', 'Building updated successfully.');
    }

    public function destroy(Building $building)
    {
        if ($building->locations()->count() > 0 || $building->meters()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete building that has locations or meters.');
        }

        $building->delete();

        return redirect()->route('buildings.index')
            ->with('success', 'Building deleted successfully.');
    }
}
