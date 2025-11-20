<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meter;
use App\Models\MeterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MeterReadingController extends Controller
{
    /**
     * Ingest meter reading from legacy gateway/meter POST
     * 
     * This endpoint maintains backward compatibility with deployed IoT devices.
     * See docs/METER_DATA_API.md for complete protocol documentation.
     */
    public function ingest(Request $request)
    {
        $startTime = microtime(true);
        
        try {
            // Check if we should save the data
            $saveToMeterData = $request->input('save_to_meter_data', 1);
            
            if ($saveToMeterData != 1) {
                // Legacy behavior: return OK even if not saving
                return $this->successResponse();
            }
            
            // Extract control parameters
            $meterIdentifier = $request->input('meter_id');
            $locationCode = $request->input('location');
            $datetime = $this->parseDateTime($request->input('datetime'));
            
            if (!$meterIdentifier) {
                Log::warning('Meter reading received without meter_id', [
                    'location' => $locationCode,
                    'ip' => $request->ip(),
                ]);
                // Still return OK to not break legacy devices
                return $this->successResponse();
            }
            
            // Lookup meter by name (legacy uses meter serial number as name)
            $meter = Meter::where('name', $meterIdentifier)->first();
            
            if (!$meter) {
                Log::warning('Meter reading received for unknown meter', [
                    'meter_id' => $meterIdentifier,
                    'location' => $locationCode,
                    'ip' => $request->ip(),
                ]);
                // Still return OK - don't break the device
                return $this->successResponse();
            }
            
            // Check for duplicate reading (same meter + timestamp)
            $existingReading = MeterData::where('meter_name', $meter->name)
                ->where('reading_datetime', $datetime)
                ->exists();
                
            if ($existingReading) {
                Log::info('Duplicate meter reading ignored', [
                    'meter_name' => $meter->name,
                    'datetime' => $datetime,
                ]);
                return $this->successResponse();
            }
            
            // Map legacy POST fields to database columns
            $meterData = [
                'location' => $locationCode ?? 'Unknown',
                'meter_name' => $meter->name,
                'reading_datetime' => $datetime,
                
                // Voltage measurements
                'vrms_a' => $this->parseFloat($request->input('vrms_a')),
                'vrms_b' => $this->parseFloat($request->input('vrms_b')),
                'vrms_c' => $this->parseFloat($request->input('vrms_c')),
                
                // Current measurements
                'irms_a' => $this->parseFloat($request->input('irms_a')),
                'irms_b' => $this->parseFloat($request->input('irms_b')),
                'irms_c' => $this->parseFloat($request->input('irms_c')),
                
                // Power measurements (map legacy field names)
                'frequency' => $this->parseFloat($request->input('freq')),
                'power_factor' => $this->parseFloat($request->input('pf')),
                'watt' => $this->parseFloat($request->input('watt')),
                'va' => $this->parseFloat($request->input('va')),
                'var' => $this->parseFloat($request->input('var')),
                
                // Energy measurements (map legacy field names)
                'wh_delivered' => $this->parseFloat($request->input('wh_del')),
                'wh_received' => $this->parseFloat($request->input('wh_rec')),
                'wh_net' => $this->parseFloat($request->input('wh_net')),
                'wh_total' => $this->parseFloat($request->input('wh_total')),
                
                // Reactive energy (map legacy field names)
                'varh_negative' => $this->parseFloat($request->input('varh_neg')),
                'varh_positive' => $this->parseFloat($request->input('varh_pos')),
                'varh_net' => $this->parseFloat($request->input('varh_net')),
                'varh_total' => $this->parseFloat($request->input('varh_total')),
                
                // Apparent energy
                'vah_total' => $this->parseFloat($request->input('vah_total')),
                
                // Demand measurements (map legacy field names)
                'max_rec_kw_demand' => $this->parseFloat($request->input('max_rec_kw_dmd')),
                'max_rec_kw_demand_time' => $this->parseDemandTime($request->input('max_rec_kw_dmd_time')),
                'max_del_kw_demand' => $this->parseFloat($request->input('max_del_kw_dmd')),
                'max_del_kw_demand_time' => $this->parseDemandTime($request->input('max_del_kw_dmd_time')),
                'max_pos_kvar_demand' => $this->parseFloat($request->input('max_pos_kvar_dmd')),
                'max_pos_kvar_demand_time' => $this->parseDemandTime($request->input('max_pos_kvar_dmd_time')),
                'max_neg_kvar_demand' => $this->parseFloat($request->input('max_neg_kvar_dmd')),
                'max_neg_kvar_demand_time' => $this->parseDemandTime($request->input('max_neg_kvar_dmd_time')),
                
                // Phase angles (map legacy field names)
                'v_phase_angle_a' => $this->parseFloat($request->input('v_ph_angle_a')),
                'v_phase_angle_b' => $this->parseFloat($request->input('v_ph_angle_b')),
                'v_phase_angle_c' => $this->parseFloat($request->input('v_ph_angle_c')),
                'i_phase_angle_a' => $this->parseFloat($request->input('i_ph_angle_a')),
                'i_phase_angle_b' => $this->parseFloat($request->input('i_ph_angle_b')),
                'i_phase_angle_c' => $this->parseFloat($request->input('i_ph_angle_c')),
                
                // Device metadata
                'mac_address' => $request->input('mac_address'),
                'software_version' => $request->input('soft_rev'),
                'relay_status' => $this->parseBoolean($request->input('relay_status')),
            ];
            
            // Insert meter reading
            MeterData::create($meterData);
            
            // Conditionally update meter metadata (12AM/12PM only)
            $this->updateMeterMetadata(
                $meter,
                $datetime,
                $request->input('soft_rev')
            );
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Meter reading ingested', [
                'meter_name' => $meter->name,
                'location' => $locationCode,
                'datetime' => $datetime,
                'wh_total' => $meterData['wh_total'],
                'duration_ms' => $duration,
            ]);
            
            return $this->successResponse();
            
        } catch (\Exception $e) {
            Log::error('Failed to ingest meter reading', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'meter_id' => $request->input('meter_id'),
                'location' => $request->input('location'),
                'ip' => $request->ip(),
            ]);
            
            // Still return OK - legacy behavior was to suppress errors
            return $this->successResponse();
        }
    }
    
    /**
     * Parse datetime from legacy format
     */
    private function parseDateTime(?string $datetime): ?string
    {
        if (!$datetime) {
            return null;
        }
        
        // Handle URL-encoded spaces (%20)
        $datetime = str_replace('%20', ' ', $datetime);
        
        try {
            $dt = new \DateTime($datetime);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::warning('Invalid datetime format', ['datetime' => $datetime]);
            return null;
        }
    }
    
    /**
     * Parse demand time (can be empty or invalid)
     */
    private function parseDemandTime(?string $datetime): ?string
    {
        if (!$datetime || $datetime === '0000-00-00 00:00:00') {
            return null;
        }
        
        return $this->parseDateTime($datetime);
    }
    
    /**
     * Parse float value with default
     */
    private function parseFloat($value, float $default = 0.0): float
    {
        if ($value === null || $value === '') {
            return $default;
        }
        
        return (float) $value;
    }
    
    /**
     * Parse boolean value
     */
    private function parseBoolean($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        return (bool) $value;
    }
    
    /**
     * Update meter metadata (last_log_update, firmware) - only at 12AM/12PM
     * 
     * Legacy behavior: Only updates twice daily to reduce DB writes
     */
    private function updateMeterMetadata(Meter $meter, ?string $readingDatetime, ?string $softwareVersion): void
    {
        if (!$readingDatetime) {
            return;
        }
        
        try {
            $readingDt = new \DateTime($readingDatetime);
            $serverDt = new \DateTime();
            
            // Check if reading is in current hour
            $readingDate = $readingDt->format('Y-m-d');
            $readingHour = $readingDt->format('H');
            $serverDate = $serverDt->format('Y-m-d');
            $serverHour = $serverDt->format('H');
            
            if ($readingDate !== $serverDate || $readingHour !== $serverHour) {
                return;
            }
            
            // Only update at 12AM (00:00-00:14) or 12PM (12:00-12:14)
            $serverMinute = (int) $serverDt->format('i');
            $isUpdateWindow = (
                ($serverHour === '00' || $serverHour === '12') && 
                ($serverMinute >= 0 && $serverMinute <= 14)
            );
            
            if (!$isUpdateWindow) {
                return;
            }
            
            // Update meter metadata
            $updates = [
                'last_log_update' => $readingDatetime,
            ];
            
            if ($softwareVersion) {
                $updates['software_version'] = $softwareVersion;
            }
            
            $meter->update($updates);
            
            Log::info('Meter metadata updated', [
                'meter_name' => $meter->name,
                'last_log_update' => $readingDatetime,
                'software_version' => $softwareVersion,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update meter metadata', [
                'meter_name' => $meter->name,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Return legacy success response format
     */
    private function successResponse()
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        return response("OK, $timestamp", 200)
            ->header('Content-Type', 'text/plain');
    }
}
