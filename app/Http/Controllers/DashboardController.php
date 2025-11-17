<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\ConfigurationFile;
use App\Models\Gateway;
use App\Models\Location;
use App\Models\Meter;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Get statistics
        $stats = [
            'sites' => [
                'total' => Site::count(),
                'online' => Site::where('status', 'Online')->count(),
                'offline' => Site::where('status', 'Offline')->count(),
            ],
            'gateways' => [
                'total' => Gateway::count(),
                'online' => Gateway::where('status', 'Online')->count(),
                'offline' => Gateway::where('status', 'Offline')->count(),
            ],
            'meters' => [
                'total' => Meter::count(),
                'active' => Meter::where('status', 'active')->count(),
                'inactive' => Meter::where('status', 'inactive')->count(),
            ],
            'buildings' => Building::count(),
            'locations' => Location::count(),
            'config_files' => ConfigurationFile::count(),
            'users' => User::count(),
        ];

        // Get recent activity (last 10 created/updated items)
        $recentActivity = collect();

        // Recent sites
        Site::latest('created_at')
            ->limit(3)
            ->get()
            ->each(function ($site) use ($recentActivity) {
                $recentActivity->push([
                    'type' => 'site',
                    'action' => 'created',
                    'description' => "Site '{$site->code}' was created",
                    'timestamp' => $site->created_at,
                    'url' => route('sites.show', $site),
                ]);
            });

        // Recent gateways
        Gateway::with('site')
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->each(function ($gateway) use ($recentActivity) {
                $recentActivity->push([
                    'type' => 'gateway',
                    'action' => 'created',
                    'description' => "Gateway '{$gateway->serial_number}' added to site {$gateway->site->code}",
                    'timestamp' => $gateway->created_at,
                    'url' => route('gateways.show', $gateway),
                ]);
            });

        // Recent meters
        Meter::with('gateway.site')
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->each(function ($meter) use ($recentActivity) {
                $recentActivity->push([
                    'type' => 'meter',
                    'action' => 'created',
                    'description' => "Meter '{$meter->name}' added to gateway {$meter->gateway->serial_number}",
                    'timestamp' => $meter->created_at,
                    'url' => route('meters.show', $meter),
                ]);
            });

        // Sort by timestamp descending and take top 10
        $recentActivity = $recentActivity
            ->sortByDesc('timestamp')
            ->take(10)
            ->values();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentActivity' => $recentActivity,
        ]);
    }
}
