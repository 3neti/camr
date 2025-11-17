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
            'days' => 'integer|min:1|max:30',
            'interval' => 'in:hour,day',
        ]);

        $days = $request->integer('days', 7);
        $interval = $request->input('interval', 'hour');
        
        $startDate = Carbon::now()->subDays($days);
        
        $query = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $startDate)
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
            'days' => 'integer|min:1|max:7',
        ]);

        $days = $request->integer('days', 1);
        $startDate = Carbon::now()->subDays($days);
        
        $data = LoadProfile::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $startDate)
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
            'days' => 'integer|min:1|max:30',
        ]);

        $days = $request->integer('days', 30);
        $startDate = Carbon::now()->subDays($days);
        
        $latest = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $startDate)
            ->orderBy('reading_datetime', 'desc')
            ->first();
            
        $oldest = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $startDate)
            ->orderBy('reading_datetime', 'asc')
            ->first();
            
        if (!$latest || !$oldest) {
            return response()->json([
                'error' => 'No data available for this period',
            ], 404);
        }
        
        $dailyAvg = MeterData::where('meter_name', $meter->name)
            ->where('reading_datetime', '>=', $startDate)
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
}
