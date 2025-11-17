<?php

namespace App\Http\Controllers;

use App\Http\Requests\{StoreSiteRequest, UpdateSiteRequest};
use App\Models\{Company, Division, Site};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Site::with(['company', 'division'])
            ->when($request->search, function ($query, $search) {
                $query->where('code', 'like', "%{$search}%");
            });

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortColumn, ['code', 'status', 'created_at'])) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->latest();
        }

        $sites = $query->paginate(15)->withQueryString();

        return Inertia::render('sites/Index', [
            'sites' => $sites,
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('sites/Create', [
            'companies' => Company::all(['id', 'code', 'name']),
            'divisions' => Division::all(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreSiteRequest $request)
    {
        Site::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('sites.index')
            ->with('success', 'Site created successfully.');
    }

    public function show(Site $site): Response
    {
        $site->load([
            'company',
            'division',
            'buildings',
            'gateways' => fn ($q) => $q->with('location'),
            'meters' => fn ($q) => $q->with(['gateway', 'location']),
        ]);

        return Inertia::render('sites/Show', [
            'site' => $site,
        ]);
    }

    public function edit(Site $site): Response
    {
        $site->load(['company', 'division']);

        return Inertia::render('sites/Edit', [
            'site' => $site,
            'companies' => Company::all(['id', 'code', 'name']),
            'divisions' => Division::all(['id', 'code', 'name']),
        ]);
    }

    public function update(UpdateSiteRequest $request, Site $site)
    {
        $site->update([
            ...$request->validated(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('sites.show', $site)
            ->with('success', 'Site updated successfully.');
    }

    public function destroy(Site $site)
    {
        $site->delete();

        return redirect()->route('sites.index')
            ->with('success', 'Site deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:sites,id',
        ]);

        $count = Site::whereIn('id', $request->ids)->delete();

        return redirect()->route('sites.index')
            ->with('success', "{$count} sites deleted successfully.");
    }
}
