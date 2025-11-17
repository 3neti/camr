<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\ConfigurationFile;
use App\Models\Gateway;
use App\Models\Location;
use App\Models\Meter;
use App\Models\MeterData;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Get energy consumption trend (last 30 days)
        $startDate = Carbon::now()->subDays(30);
        $consumptionTrend = MeterData::where('reading_datetime', '>=', $startDate)
            ->selectRaw('
                DATE(reading_datetime) as date,
                SUM(watt) / COUNT(DISTINCT meter_name) as avg_power,
                COUNT(DISTINCT meter_name) as meter_count
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($record) {
                return [
                    'date' => $record->date,
                    'power' => round($record->avg_power / 1000, 2), // Convert to kW
                    'meters' => $record->meter_count,
                ];
            });

        // Get top consuming meters (last 7 days)
        $topMeters = MeterData::where('reading_datetime', '>=', Carbon::now()->subDays(7))
            ->selectRaw('
                meter_name,
                MAX(wh_delivered) - MIN(wh_delivered) as consumption
            ')
            ->groupBy('meter_name')
            ->orderByDesc('consumption')
            ->limit(5)
            ->get()
            ->map(function ($record) {
                return [
                    'name' => $record->meter_name,
                    'consumption' => round($record->consumption / 1000, 2), // Convert to kWh
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'consumptionTrend' => $consumptionTrend,
            'topMeters' => $topMeters,
        ]);
    }
}
