<?php

namespace App\Http\Controllers;

use App\Models\Meter;
use App\Models\MeterData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get real-time power consumption data
     */
    public function realtimePower(Request $request)
    {
        $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'meter_ids' => 'nullable|array',
            'meter_ids.*' => 'exists:meters,id',
        ]);
        
        $query = Meter::query()->with(['meterData' => function ($q) {
            $q->latest('reading_datetime')->limit(1);
        }]);
        
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }
        
        if ($request->filled('meter_ids')) {
            $query->whereIn('id', $request->meter_ids);
        }
        
        $meters = $query->get();
        
        $data = $meters->map(function ($meter) {
            $latestReading = $meter->meterData->first();
            
            if (!$latestReading) {
                return null;
            }
            
            return [
                'meter_id' => $meter->id,
                'meter_name' => $meter->name,
                'power_kw' => round((float) $latestReading->watt, 2),
                'voltage' => [
                    'a' => round((float) $latestReading->vrms_a, 2),
                    'b' => round((float) $latestReading->vrms_b, 2),
                    'c' => round((float) $latestReading->vrms_c, 2),
                    'avg' => round((
                        (float) $latestReading->vrms_a + 
                        (float) $latestReading->vrms_b + 
                        (float) $latestReading->vrms_c
                    ) / 3, 2),
                ],
                'current' => [
                    'a' => round((float) $latestReading->irms_a, 2),
                    'b' => round((float) $latestReading->irms_b, 2),
                    'c' => round((float) $latestReading->irms_c, 2),
                    'avg' => round((
                        (float) $latestReading->irms_a + 
                        (float) $latestReading->irms_b + 
                        (float) $latestReading->irms_c
                    ) / 3, 2),
                ],
                'power_factor' => round((float) $latestReading->power_factor, 3),
                'frequency' => round((float) $latestReading->frequency, 2),
                'timestamp' => $latestReading->reading_datetime->toIso8601String(),
                'is_recent' => $latestReading->reading_datetime->isAfter(now()->subMinutes(15)),
            ];
        })->filter()->values();
        
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'meters' => $data,
            'summary' => [
                'total_power_kw' => round($data->sum('power_kw'), 2),
                'meter_count' => $data->count(),
                'online_count' => $data->where('is_recent', true)->count(),
            ],
        ]);
    }
    
    /**
     * Get energy consumption trend over time
     */
    public function energyTrend(Request $request)
    {
        $request->validate([
            'period' => 'required|in:hourly,daily,weekly,monthly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'site_id' => 'nullable|exists:sites,id',
            'meter_ids' => 'nullable|array',
        ]);
        
        $period = $request->period;
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        
        // Build base query
        $query = MeterData::whereBetween('reading_datetime', [$startDate, $endDate]);
        
        if ($request->filled('site_id')) {
            $query->whereIn('meter_name', function ($q) use ($request) {
                $q->select('name')->from('meters')->where('site_id', $request->site_id);
            });
        }
        
        if ($request->filled('meter_ids')) {
            $query->whereIn('meter_name', function ($q) use ($request) {
                $q->select('name')->from('meters')->whereIn('id', $request->meter_ids);
            });
        }
        
        // Group by period
        switch ($period) {
            case 'hourly':
                $groupBy = "strftime('%Y-%m-%d %H:00:00', reading_datetime)";
                break;
            case 'daily':
                $groupBy = "DATE(reading_datetime)";
                break;
            case 'weekly':
                $groupBy = "strftime('%Y-W%W', reading_datetime)";
                break;
            case 'monthly':
                $groupBy = "strftime('%Y-%m', reading_datetime)";
                break;
        }
        
        $data = $query->selectRaw("
            {$groupBy} as period,
            meter_name,
            MAX(wh_total) - MIN(wh_total) as energy_consumed,
            AVG(watt) as avg_power,
            MAX(watt) as max_power
        ")
        ->groupBy('period', 'meter_name')
        ->get();
        
        // Aggregate by period
        $aggregated = $data->groupBy('period')->map(function ($items) {
            return [
                'period' => $items->first()->period,
                'total_energy_kwh' => round($items->sum('energy_consumed'), 2),
                'avg_power_kw' => round($items->avg('avg_power'), 2),
                'max_power_kw' => round($items->max('max_power'), 2),
                'meter_count' => $items->count(),
            ];
        })->values();
        
        return response()->json([
            'period_type' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'data' => $aggregated,
        ]);
    }
    
    /**
     * Get power quality metrics
     */
    public function powerQuality(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'site_id' => 'nullable|exists:sites,id',
            'meter_ids' => 'nullable|array',
        ]);
        
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        
        $query = MeterData::whereBetween('reading_datetime', [$startDate, $endDate]);
        
        if ($request->filled('site_id')) {
            $query->whereIn('meter_name', function ($q) use ($request) {
                $q->select('name')->from('meters')->where('site_id', $request->site_id);
            });
        }
        
        if ($request->filled('meter_ids')) {
            $query->whereIn('meter_name', function ($q) use ($request) {
                $q->select('name')->from('meters')->whereIn('id', $request->meter_ids);
            });
        }
        
        $metrics = $query->selectRaw('
            COUNT(*) as total_readings,
            AVG((vrms_a + vrms_b + vrms_c) / 3) as avg_voltage,
            MIN((vrms_a + vrms_b + vrms_c) / 3) as min_voltage,
            MAX((vrms_a + vrms_b + vrms_c) / 3) as max_voltage,
            AVG((irms_a + irms_b + irms_c) / 3) as avg_current,
            MIN((irms_a + irms_b + irms_c) / 3) as min_current,
            MAX((irms_a + irms_b + irms_c) / 3) as max_current,
            AVG(power_factor) as avg_power_factor,
            MIN(power_factor) as min_power_factor,
            MAX(power_factor) as max_power_factor,
            AVG(frequency) as avg_frequency,
            MIN(frequency) as min_frequency,
            MAX(frequency) as max_frequency,
            SUM(CASE WHEN power_factor < 0.9 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as low_pf_percentage
        ')->first();
        
        // Get voltage/current by phase for detailed analysis
        $phaseData = $query->selectRaw('
            AVG(vrms_a) as avg_voltage_a,
            AVG(vrms_b) as avg_voltage_b,
            AVG(vrms_c) as avg_voltage_c,
            AVG(irms_a) as avg_current_a,
            AVG(irms_b) as avg_current_b,
            AVG(irms_c) as avg_current_c
        ')->first();
        
        return response()->json([
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_readings' => $metrics->total_readings,
            'voltage' => [
                'avg' => round((float) $metrics->avg_voltage, 2),
                'min' => round((float) $metrics->min_voltage, 2),
                'max' => round((float) $metrics->max_voltage, 2),
                'by_phase' => [
                    'a' => round((float) $phaseData->avg_voltage_a, 2),
                    'b' => round((float) $phaseData->avg_voltage_b, 2),
                    'c' => round((float) $phaseData->avg_voltage_c, 2),
                ],
            ],
            'current' => [
                'avg' => round((float) $metrics->avg_current, 2),
                'min' => round((float) $metrics->min_current, 2),
                'max' => round((float) $metrics->max_current, 2),
                'by_phase' => [
                    'a' => round((float) $phaseData->avg_current_a, 2),
                    'b' => round((float) $phaseData->avg_current_b, 2),
                    'c' => round((float) $phaseData->avg_current_c, 2),
                ],
            ],
            'power_factor' => [
                'avg' => round((float) $metrics->avg_power_factor, 3),
                'min' => round((float) $metrics->min_power_factor, 3),
                'max' => round((float) $metrics->max_power_factor, 3),
                'low_pf_percentage' => round((float) $metrics->low_pf_percentage, 2),
            ],
            'frequency' => [
                'avg' => round((float) $metrics->avg_frequency, 2),
                'min' => round((float) $metrics->min_frequency, 2),
                'max' => round((float) $metrics->max_frequency, 2),
            ],
        ]);
    }
    
    /**
     * Get demand analysis
     */
    public function demandAnalysis(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'site_id' => 'nullable|exists:sites,id',
            'meter_ids' => 'nullable|array',
        ]);
        
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        
        $query = MeterData::whereBetween('reading_datetime', [$startDate, $endDate])
            ->whereNotNull('max_del_kw_demand');
        
        if ($request->filled('site_id')) {
            $query->whereIn('meter_name', function ($q) use ($request) {
                $q->select('name')->from('meters')->where('site_id', $request->site_id);
            });
        }
        
        if ($request->filled('meter_ids')) {
            $query->whereIn('meter_name', function ($q) use ($request) {
                $q->select('name')->from('meters')->whereIn('id', $request->meter_ids);
            });
        }
        
        // Get demand peaks per day
        $dailyPeaks = $query->clone()
            ->selectRaw('
                DATE(reading_datetime) as date,
                MAX(max_del_kw_demand) as peak_demand,
                AVG(max_del_kw_demand) as avg_demand
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'peak_demand_kw' => round((float) $item->peak_demand, 2),
                    'avg_demand_kw' => round((float) $item->avg_demand, 2),
                ];
            });
        
        // Get overall statistics
        $stats = $query->selectRaw('
            MAX(max_del_kw_demand) as peak_demand,
            AVG(max_del_kw_demand) as avg_demand,
            MAX(max_pos_kvar_demand) as peak_kvar_demand,
            AVG(max_pos_kvar_demand) as avg_kvar_demand
        ')->first();
        
        return response()->json([
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'peak_demand_kw' => round((float) $stats->peak_demand, 2),
                'avg_demand_kw' => round((float) $stats->avg_demand, 2),
                'peak_kvar_demand' => round((float) $stats->peak_kvar_demand, 2),
                'avg_kvar_demand' => round((float) $stats->avg_kvar_demand, 2),
            ],
            'daily_peaks' => $dailyPeaks,
        ]);
    }
    
    /**
     * Get site/building level aggregation
     */
    public function siteAggregation(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'required|in:site,building,location',
        ]);
        
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $groupBy = $request->group_by;
        
        // Build query based on grouping
        $meterDataQuery = MeterData::whereBetween('reading_datetime', [$startDate, $endDate])
            ->selectRaw('
                meter_name,
                MAX(wh_total) - MIN(wh_total) as energy_consumed,
                AVG(watt) as avg_power,
                MAX(watt) as max_power
            ')
            ->groupBy('meter_name');
        
        $metersQuery = Meter::query()
            ->joinSub($meterDataQuery, 'meter_data', function ($join) {
                $join->on('meters.name', '=', 'meter_data.meter_name');
            })
            ->join('sites', 'meters.site_id', '=', 'sites.id');
        
        switch ($groupBy) {
            case 'site':
                $metersQuery->selectRaw('
                    sites.code as group_name,
                    sites.id as group_id,
                    SUM(meter_data.energy_consumed) as total_energy,
                    AVG(meter_data.avg_power) as avg_power,
                    MAX(meter_data.max_power) as max_power,
                    COUNT(DISTINCT meters.id) as meter_count
                ')
                ->groupBy('sites.id', 'sites.code');
                break;
                
            case 'building':
                $metersQuery->leftJoin('buildings', 'meters.building_id', '=', 'buildings.id')
                    ->selectRaw('
                        COALESCE(buildings.name, "Unassigned") as group_name,
                        buildings.id as group_id,
                        sites.code as site_code,
                        SUM(meter_data.energy_consumed) as total_energy,
                        AVG(meter_data.avg_power) as avg_power,
                        MAX(meter_data.max_power) as max_power,
                        COUNT(DISTINCT meters.id) as meter_count
                    ')
                    ->groupBy('buildings.id', 'buildings.name', 'sites.code');
                break;
                
            case 'location':
                $metersQuery->leftJoin('locations', 'meters.location_id', '=', 'locations.id')
                    ->selectRaw('
                        COALESCE(locations.name, "Unassigned") as group_name,
                        locations.id as group_id,
                        sites.code as site_code,
                        SUM(meter_data.energy_consumed) as total_energy,
                        AVG(meter_data.avg_power) as avg_power,
                        MAX(meter_data.max_power) as max_power,
                        COUNT(DISTINCT meters.id) as meter_count
                    ')
                    ->groupBy('locations.id', 'locations.name', 'sites.code');
                break;
        }
        
        $results = $metersQuery->get()->map(function ($item) {
            return [
                'group_name' => $item->group_name,
                'group_id' => $item->group_id,
                'site_code' => $item->site_code ?? null,
                'total_energy_kwh' => round((float) $item->total_energy, 2),
                'avg_power_kw' => round((float) $item->avg_power, 2),
                'max_power_kw' => round((float) $item->max_power, 2),
                'meter_count' => $item->meter_count,
            ];
        });
        
        return response()->json([
            'group_by' => $groupBy,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'data' => $results,
            'summary' => [
                'total_energy_kwh' => round($results->sum('total_energy_kwh'), 2),
                'total_groups' => $results->count(),
                'total_meters' => $results->sum('meter_count'),
            ],
        ]);
    }
    
    /**
     * Get top energy consumers
     */
    public function topConsumers(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:100',
            'site_id' => 'nullable|exists:sites,id',
        ]);
        
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $limit = $request->input('limit', 10);
        
        $query = MeterData::whereBetween('reading_datetime', [$startDate, $endDate]);
        
        if ($request->filled('site_id')) {
            $query->whereIn('meter_name', function ($q) use ($request) {
                $q->select('name')->from('meters')->where('site_id', $request->site_id);
            });
        }
        
        $data = $query->selectRaw('
            meter_name,
            MAX(wh_total) - MIN(wh_total) as energy_consumed,
            AVG(watt) as avg_power,
            MAX(watt) as max_power,
            AVG(power_factor) as avg_power_factor
        ')
        ->groupBy('meter_name')
        ->orderByDesc('energy_consumed')
        ->limit($limit)
        ->get()
        ->map(function ($item) {
            return [
                'meter_name' => $item->meter_name,
                'energy_consumed_kwh' => round((float) $item->energy_consumed, 2),
                'avg_power_kw' => round((float) $item->avg_power, 2),
                'max_power_kw' => round((float) $item->max_power, 2),
                'avg_power_factor' => round((float) $item->avg_power_factor, 3),
            ];
        });
        
        return response()->json([
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'data' => $data,
        ]);
    }
}
