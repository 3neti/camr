<?php

namespace App\Http\Controllers;

use App\Models\ConfigurationFile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationFileController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ConfigurationFile::query()->withCount('meters');

        if ($request->filled('search')) {
            $query->where('meter_model', 'like', "%{$request->search}%");
        }

        $configFiles = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('config-files/Index', [
            'configFiles' => $configFiles,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('config-files/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'meter_model' => 'required|string|max:255',
            'config_file_content' => 'required|string',
        ]);

        ConfigurationFile::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('config-files.index')
            ->with('success', 'Configuration file created successfully.');
    }

    public function show(ConfigurationFile $configFile): Response
    {
        $configFile->load(['meters.gateway.site', 'createdBy', 'updatedBy']);

        return Inertia::render('config-files/Show', [
            'configFile' => $configFile,
        ]);
    }

    public function edit(ConfigurationFile $configFile): Response
    {
        return Inertia::render('config-files/Edit', [
            'configFile' => $configFile,
        ]);
    }

    public function update(Request $request, ConfigurationFile $configFile)
    {
        $validated = $request->validate([
            'meter_model' => 'required|string|max:255',
            'config_file_content' => 'required|string',
        ]);

        $configFile->update([
            ...$validated,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('config-files.show', $configFile)
            ->with('success', 'Configuration file updated successfully.');
    }

    public function destroy(ConfigurationFile $configFile)
    {
        // Check if any meters are using this config
        if ($configFile->meters()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete configuration file that is in use by meters.');
        }

        $configFile->delete();

        return redirect()->route('config-files.index')
            ->with('success', 'Configuration file deleted successfully.');
    }
}
