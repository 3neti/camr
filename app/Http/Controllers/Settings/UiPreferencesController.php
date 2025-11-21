<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Settings\UiSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UiPreferencesController extends Controller
{
    public function edit(UiSettings $settings): Response
    {
        return Inertia::render('settings/UiPreferences', [
            'uiSettings' => [
                'show_buildings' => $settings->show_buildings,
                'show_locations' => $settings->show_locations,
                'show_config_files' => $settings->show_config_files,
            ],
        ]);
    }

    public function update(Request $request, UiSettings $settings)
    {
        $validated = $request->validate([
            'show_buildings' => 'boolean',
            'show_locations' => 'boolean',
            'show_config_files' => 'boolean',
        ]);

        $settings->show_buildings = $validated['show_buildings'] ?? false;
        $settings->show_locations = $validated['show_locations'] ?? false;
        $settings->show_config_files = $validated['show_config_files'] ?? false;
        $settings->save();

        return redirect()->back()
            ->with('success', 'Sidebar preferences updated successfully.');
    }
}
