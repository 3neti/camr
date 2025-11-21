<?php

namespace App\Http\Middleware;

use App\Settings\UiSettings;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareUiSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share([
            'uiSettings' => function () {
                return [
                    'show_buildings' => app(UiSettings::class)->show_buildings,
                    'show_locations' => app(UiSettings::class)->show_locations,
                    'show_config_files' => app(UiSettings::class)->show_config_files,
                ];
            },
        ]);

        return $next($request);
    }
}
