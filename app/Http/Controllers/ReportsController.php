<?php

namespace App\Http\Controllers;

use App\Models\Meter;
use App\Models\MeterData;
use App\Models\Site;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function index(): Response
    {
        // Get sites with meter counts
        $sites = Site::withCount('meters')->get();
        
        // Get meters with latest data
        $meters = Meter::with(['gateway.site', 'location'])
            ->limit(10)
            ->get()
            ->map(function ($meter) {
                $latestData = MeterData::where('meter_name', $meter->name)
                    ->orderBy('reading_datetime', 'desc')
                    ->first();
                    
                return [
                    'id' => $meter->id,
                    'name' => $meter->name,
                    'type' => $meter->type,
                    'site' => $meter->gateway->site->code ?? 'N/A',
                    'has_load_profile' => $meter->has_load_profile,
                    'latest_power' => $latestData?->watt,
                    'latest_energy' => $latestData?->wh_delivered,
                    'last_reading' => $latestData?->reading_datetime?->toIso8601String(),
                ];
            });
        
        // Get overall statistics
        $totalMeters = Meter::count();
        $activeMeters = Meter::where('status', 'Active')->count();
        $metersWithData = MeterData::distinct('meter_name')->count('meter_name');
        
        return Inertia::render('reports/Index', [
            'sites' => $sites,
            'meters' => $meters,
            'stats' => [
                'total_meters' => $totalMeters,
                'active_meters' => $activeMeters,
                'meters_with_data' => $metersWithData,
            ],
        ]);
    }
}
