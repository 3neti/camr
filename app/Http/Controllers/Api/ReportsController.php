<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoadProfile;
use App\Models\Meter;
use App\Models\MeterData;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    /**
     * Get power consumption time-series for a meter
     */
    public function meterPowerData(Request $request, Meter $meter)
    {
        $request->validate([
            'days' => 'integer|min:1|max:365',
            'interval' => 'in:hour,day',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
        ]);

        // Support custom date range or days parameter
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
        } else {
            $days = $request->integer('days', 7);
            $startDate = Carbon::now()->subDays($days);
            $endDate = Carbon::now();
        }
        
        $interval = $request->input('interval', 'hour');
        
        $query = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $startDate)
            ->where('reading_datetime', '<=', $endDate)
            ->orderBy('reading_datetime');
            
        if ($interval === 'day') {
            // Group by day
            $data = $query->selectRaw('
                DATE(reading_datetime) as date,
                AVG(watt) as avg_power,
                MAX(watt) as max_power,
                MIN(watt) as min_power,
                SUM(watt) as total_energy
            ')
            ->groupBy('date')
            ->get();
        } else {
            // Get all readings and calculate power from energy deltas
            $readings = $query->get();
            $data = [];
            
            for ($i = 0; $i < $readings->count(); $i++) {
                $current = $readings[$i];
                $previous = $i > 0 ? $readings[$i - 1] : null;
                
                // Calculate power from energy difference if we have previous reading
                $power = null;
                if ($previous && $current->wh_total && $previous->wh_total) {
                    $energyDelta = $current->wh_total - $previous->wh_total; // Wh
                    $timeDelta = $previous->reading_datetime->diffInHours($current->reading_datetime, true);
                    $power = $timeDelta > 0 ? ($energyDelta / $timeDelta) : 0; // W (Wh/h = W)
                }
                
                $data[] = [
                    'datetime' => $current->reading_datetime->toIso8601String(),
                    'power' => $power ?? $current->watt ?? 0,
                    'energy_total' => $current->wh_total,
                    'voltage' => (($current->vrms_a ?? 0) + ($current->vrms_b ?? 0) + ($current->vrms_c ?? 0)) / 3,
                    'current' => (($current->irms_a ?? 0) + ($current->irms_b ?? 0) + ($current->irms_c ?? 0)) / 3,
                    'power_factor' => $current->power_factor,
                ];
            }
        }
        
        return response()->json([
            'meter' => $meter->name,
            'interval' => $interval,
            'days' => $days,
            'data' => $data,
        ]);
    }

    /**
     * Get load profile data for a meter (15-min intervals)
     */
    public function meterLoadProfile(Request $request, Meter $meter)
    {
        $request->validate([
            'days' => 'integer|min:1|max:31',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
        ]);

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
        } else {
            $days = $request->integer('days', 1);
            $startDate = Carbon::now()->subDays($days);
            $endDate = Carbon::now();
        }
        
        $data = LoadProfile::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $startDate)
            ->where('reading_datetime', '<=', $endDate)
            ->orderBy('reading_datetime')
            ->get()
            ->map(function ($record) {
                return [
                    'datetime' => $record->reading_datetime->toIso8601String(),
                    'delivered_power' => $record->channel_1, // kW
                    'delivered_energy' => $record->channel_2, // kWh
                    'delivered_reactive' => $record->channel_3, // kvarh
                    'received_power' => $record->channel_4, // kW
                    'received_energy' => $record->channel_5, // kWh
                    'received_reactive' => $record->channel_6, // kvarh
                ];
            });
        
        return response()->json([
            'meter' => $meter->name,
            'days' => $days,
            'intervals' => $data->count(),
            'data' => $data,
        ]);
    }

    /**
     * Get energy consumption summary
     */
    public function meterEnergySummary(Request $request, Meter $meter)
    {
        $request->validate([
            'days' => 'integer|min:1|max:365',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
        ]);

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
        } else {
            $days = $request->integer('days', 30);
            $startDate = Carbon::now()->subDays($days);
            $endDate = Carbon::now();
        }
        
        $latest = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $startDate)
            ->where('reading_datetime', '<=', $endDate)
            ->orderBy('reading_datetime', 'desc')
            ->first();
            
        $oldest = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $startDate)
            ->where('reading_datetime', '<=', $endDate)
            ->orderBy('reading_datetime', 'asc')
            ->first();
            
        if (!$latest || !$oldest) {
            return response()->json([
                'error' => 'No data available for this period',
            ], 404);
        }
        
        $dailyAvg = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $startDate)
            ->where('reading_datetime', '<=', $endDate)
            ->selectRaw('
                DATE(reading_datetime) as date,
                AVG(watt) as avg_power,
                MAX(watt) as peak_power
            ')
            ->groupBy('date')
            ->get();
        
        // Calculate consumption from wh_total delta
        // If only one reading, use the wh_total value as cumulative energy; otherwise calculate delta
        if ($latest->id === $oldest->id) {
            // Single reading: use wh_total as the cumulative consumed energy
            // and wh_delivered if available (some systems track delivered separately)
            $totalConsumed = ($latest->wh_delivered && $latest->wh_delivered > 0) 
                ? $latest->wh_delivered 
                : ($latest->wh_total ?? 0);
            $avgPower = $latest->watt ?? 0;
        } else {
            // Multiple readings: calculate delta from start to end
            $totalConsumed = ($latest->wh_total ?? 0) - ($oldest->wh_total ?? 0);
            $hoursElapsed = $oldest->reading_datetime->diffInHours($latest->reading_datetime);
            $avgPower = $hoursElapsed > 0 ? ($totalConsumed / $hoursElapsed) : 0;
        }
        
        // Get peak power from max demand fields (if available)
        $peakPower = max(
            $latest->max_del_kw_demand ?? 0,
            $latest->max_rec_kw_demand ?? 0
        ) * 1000; // Convert kW to W
        
        return response()->json([
            'meter' => $meter->name,
            'period_days' => $days ?? ($oldest->reading_datetime->diffInDays($latest->reading_datetime)),
            'total_delivered' => $totalConsumed, // Total energy consumed
            'total_received' => ($latest->wh_received ?? 0) - ($oldest->wh_received ?? 0),
            'net_consumption' => $totalConsumed,
            'avg_power' => $avgPower,
            'peak_power' => $peakPower,
            'period_start' => $oldest->reading_datetime->toIso8601String(),
            'period_end' => $latest->reading_datetime->toIso8601String(),
        ]);
    }

    /**
     * Get period comparison (MoM or YoY)
     */
    public function meterComparison(Request $request, Meter $meter)
    {
        $request->validate([
            'current_start' => 'required|date',
            'current_end' => 'required|date|after_or_equal:current_start',
            'previous_start' => 'required|date',
            'previous_end' => 'required|date|after_or_equal:previous_start',
        ]);

        $currentStart = Carbon::parse($request->input('current_start'));
        $currentEnd = Carbon::parse($request->input('current_end'));
        $previousStart = Carbon::parse($request->input('previous_start'));
        $previousEnd = Carbon::parse($request->input('previous_end'));

        // Get current period data
        $currentLatest = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $currentStart)
            ->where('reading_datetime', '<=', $currentEnd)
            ->orderBy('reading_datetime', 'desc')
            ->first();

        $currentOldest = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $currentStart)
            ->where('reading_datetime', '<=', $currentEnd)
            ->orderBy('reading_datetime', 'asc')
            ->first();

        // Get previous period data
        $previousLatest = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $previousStart)
            ->where('reading_datetime', '<=', $previousEnd)
            ->orderBy('reading_datetime', 'desc')
            ->first();

        $previousOldest = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $previousStart)
            ->where('reading_datetime', '<=', $previousEnd)
            ->orderBy('reading_datetime', 'asc')
            ->first();

        // Calculate consumption for both periods
        $currentConsumption = ($currentLatest && $currentOldest) 
            ? $currentLatest->wh_delivered - $currentOldest->wh_delivered 
            : 0;

        $previousConsumption = ($previousLatest && $previousOldest) 
            ? $previousLatest->wh_delivered - $previousOldest->wh_delivered 
            : 0;

        // Calculate averages
        $currentAvg = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $currentStart)
            ->where('reading_datetime', '<=', $currentEnd)
            ->avg('watt');

        $previousAvg = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $previousStart)
            ->where('reading_datetime', '<=', $previousEnd)
            ->avg('watt');

        // Calculate change
        $change = $currentConsumption - $previousConsumption;
        $changePercent = $previousConsumption > 0 
            ? ($change / $previousConsumption) * 100 
            : 0;

        return response()->json([
            'meter' => $meter->name,
            'current' => [
                'start' => $currentStart->toIso8601String(),
                'end' => $currentEnd->toIso8601String(),
                'consumption' => $currentConsumption,
                'avg_power' => $currentAvg,
                'readings_count' => MeterData::where('meter_name', $meter->name)
                    ->where('reading_datetime', '>=', $currentStart)
                    ->where('reading_datetime', '<=', $currentEnd)
                    ->count(),
            ],
            'previous' => [
                'start' => $previousStart->toIso8601String(),
                'end' => $previousEnd->toIso8601String(),
                'consumption' => $previousConsumption,
                'avg_power' => $previousAvg,
                'readings_count' => MeterData::where('meter_name', $meter->name)
                    ->where('reading_datetime', '>=', $previousStart)
                    ->where('reading_datetime', '<=', $previousEnd)
                    ->count(),
            ],
            'comparison' => [
                'change' => $change,
                'change_percent' => round($changePercent, 2),
                'trend' => $change >= 0 ? 'up' : 'down',
            ],
        ]);
    }
}
