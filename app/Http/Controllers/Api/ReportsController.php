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
            // Hourly data
            $data = $query->get()->map(function ($record) {
                return [
                    'datetime' => $record->reading_datetime->toIso8601String(),
                    'power' => $record->watt,
                    'voltage' => ($record->vrms_a + $record->vrms_b + $record->vrms_c) / 3,
                    'current' => ($record->irms_a + $record->irms_b + $record->irms_c) / 3,
                    'power_factor' => $record->power_factor,
                    'energy_delivered' => $record->wh_delivered,
                ];
            });
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
        
        return response()->json([
            'meter' => $meter->name,
            'period_days' => $days,
            'total_delivered' => $latest->wh_delivered - $oldest->wh_delivered,
            'total_received' => $latest->wh_received - $oldest->wh_received,
            'net_consumption' => ($latest->wh_delivered - $oldest->wh_delivered) - ($latest->wh_received - $oldest->wh_received),
            'avg_power' => $dailyAvg->avg('avg_power'),
            'peak_power' => $dailyAvg->max('peak_power'),
            'daily_breakdown' => $dailyAvg,
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
