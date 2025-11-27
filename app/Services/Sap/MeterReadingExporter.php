<?php

namespace App\Services\Sap;

use App\Models\Site;
use App\Models\Meter;
use App\Models\MeterData;
use App\Models\SapExportLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MeterReadingExporter
{
    protected array $config;
    protected ?SapExportLog $log = null;

    public function __construct()
    {
        $this->config = config('sap');
    }

    /**
     * Export meter readings for all sites with today's cut-off
     */
    public function export(?int $cutoffDay = null): array
    {
        $results = [
            'success' => false,
            'sites_processed' => 0,
            'total_meters_exported' => 0,
            'files_created' => [],
            'errors' => [],
        ];

        if (!$this->isEnabled()) {
            $results['errors'][] = "SAP export is disabled";
            return $results;
        }

        $targetDay = $cutoffDay ?? now()->day;
        
        // Get sites with this cut-off day
        $sites = Site::where('sap_cut_off_day', $targetDay)
            ->whereNotNull('sap_business_entity')
            ->whereNotNull('sap_company_code')
            ->get();

        if ($sites->isEmpty()) {
            Log::info("No sites found with cut-off day {$targetDay}");
            return $results;
        }

        foreach ($sites as $site) {
            try {
                $exportResult = $this->exportSite($site);
                
                if ($exportResult['success']) {
                    $results['sites_processed']++;
                    $results['total_meters_exported'] += $exportResult['meters_exported'];
                    $results['files_created'][] = $exportResult['file_path'];
                    $results['success'] = true;
                }
            } catch (\Exception $e) {
                Log::error("Error exporting site {$site->code}: " . $e->getMessage());
                $results['errors'][] = "Site {$site->code}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Export readings for a single site
     */
    protected function exportSite(Site $site): array
    {
        $startTime = now();
        $cutoffDate = now()->startOfDay();
        $cutoffTime = $this->config['export']['cutoff_time'];
        $cutoffDateTime = Carbon::parse($cutoffDate->format('Y-m-d') . ' ' . $cutoffTime);

        // Create export log
        $this->log = SapExportLog::create([
            'site_id' => $site->id,
            'business_entity' => $site->sap_business_entity,
            'company_code' => $site->sap_company_code,
            'cut_off_date' => $cutoffDate,
            'status' => 'processing',
            'started_at' => $startTime,
        ]);

        try {
            // Query meters with readings
            $metersWithReadings = $this->getMetersWithReadings($site, $cutoffDateTime);

            Log::info("Found {$metersWithReadings->count()} meters for export at site {$site->code}");

            // Filter and validate meters
            $validMeters = [];
            $skippedMeters = 0;
            $validationSummary = [];

            foreach ($metersWithReadings as $meterData) {
                $validation = $this->validateMeterForExport($meterData, $cutoffDate);
                
                if ($validation['valid']) {
                    $validMeters[] = $meterData;
                } else {
                    $skippedMeters++;
                    $reason = $validation['reason'] ?? 'Unknown';
                    $validationSummary[$reason] = ($validationSummary[$reason] ?? 0) + 1;
                }
            }

            // Check if file already exists
            $fileName = $this->generateFileName($site, $cutoffDate);
            $filePath = $this->getFilePath($fileName);

            if (file_exists($filePath)) {
                throw new \Exception("Export file already exists: {$fileName}");
            }

            // Generate CSV content
            $csvContent = $this->generateCsvContent($validMeters);

            // Write file
            $exportPath = $this->config['export']['path'];
            if (!is_dir($exportPath)) {
                mkdir($exportPath, 0755, true);
            }

            file_put_contents($filePath, $csvContent);

            // Update log
            $this->log->update([
                'file_name' => $fileName,
                'file_path' => $filePath,
                'status' => 'success',
                'total_meters' => count($metersWithReadings),
                'exported_meters' => count($validMeters),
                'skipped_meters' => $skippedMeters,
                'validation_summary' => json_encode($validationSummary),
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($startTime),
            ]);

            Log::info("Exported {$fileName}: " . count($validMeters) . " meters, {$skippedMeters} skipped");

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'meters_exported' => count($validMeters),
                'meters_skipped' => $skippedMeters,
            ];

        } catch (\Exception $e) {
            $this->log->update([
                'status' => 'failed',
                'errors' => json_encode([$e->getMessage()]),
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($startTime),
            ]);

            throw $e;
        }
    }

    /**
     * Get meters with their latest readings
     */
    protected function getMetersWithReadings(Site $site, Carbon $cutoffDateTime)
    {
        return DB::table('meters')
            ->select(
                'meters.id',
                'meters.name as meter_name',
                'meters.customer_name',
                'meters.status',
                'meters.role',
                'meters.sap_measuring_point',
                'meters.sap_measuring_point_desc',
                'meters.sap_contract_number',
                'meters.sap_ro_valid_from',
                'meters.sap_ro_valid_to',
                DB::raw('(SELECT wh_total FROM meter_data 
                    WHERE meter_data.meter_name = meters.name 
                    AND meter_data.location = ? 
                    AND meter_data.reading_datetime <= ? 
                    ORDER BY meter_data.reading_datetime DESC 
                    LIMIT 1) as latest_reading'),
                DB::raw('(SELECT reading_datetime FROM meter_data 
                    WHERE meter_data.meter_name = meters.name 
                    AND meter_data.location = ? 
                    AND meter_data.reading_datetime <= ? 
                    ORDER BY meter_data.reading_datetime DESC 
                    LIMIT 1) as latest_reading_time')
            )
            ->where('meters.site_id', $site->id)
            ->where('meters.site_code', '!=', 'unassigned')
            ->setBindings([$site->code, $cutoffDateTime, $site->code, $cutoffDateTime])
            ->get();
    }

    /**
     * Validate if meter should be exported
     */
    protected function validateMeterForExport($meterData, Carbon $cutoffDate): array
    {
        $validation = $this->config['export']['validation'];

        // Check if reading exists
        if (empty($meterData->latest_reading) || empty($meterData->latest_reading_time)) {
            return ['valid' => false, 'reason' => 'No reading'];
        }

        // Check reading value
        if ($meterData->latest_reading < $validation['min_reading_value']) {
            return ['valid' => false, 'reason' => 'Reading too low'];
        }

        // Check if reading contains invalid exponent notation
        if (str_contains((string)$meterData->latest_reading, 'E')) {
            return ['valid' => false, 'reason' => 'Invalid reading format'];
        }

        // Check reading freshness (max offline days)
        $readingTime = Carbon::parse($meterData->latest_reading_time);
        $daysOffline = $cutoffDate->diffInDays($readingTime);
        
        if ($daysOffline > $validation['max_offline_days']) {
            return ['valid' => false, 'reason' => "Offline {$daysOffline} days"];
        }

        // Check meter status
        if ($validation['require_active_status']) {
            if (strtolower($meterData->status) !== 'active') {
                return ['valid' => false, 'reason' => 'Inactive meter'];
            }
        }

        // Check meter role
        if ($meterData->role !== 'Client Meter') {
            return ['valid' => false, 'reason' => 'Not client meter'];
        }

        // Check customer name
        if ($validation['require_customer_name'] && empty($meterData->customer_name)) {
            return ['valid' => false, 'reason' => 'No customer name'];
        }

        // Check contract number
        if ($validation['require_contract_number'] && empty($meterData->sap_contract_number)) {
            return ['valid' => false, 'reason' => 'No contract number'];
        }

        // Check measuring point
        if ($validation['require_measuring_point'] && empty($meterData->sap_measuring_point)) {
            return ['valid' => false, 'reason' => 'No measuring point'];
        }

        // Check RO validity
        if ($validation['require_valid_ro']) {
            if (empty($meterData->sap_ro_valid_to)) {
                return ['valid' => false, 'reason' => 'No RO validity'];
            }

            $roValidTo = Carbon::parse($meterData->sap_ro_valid_to);
            if ($roValidTo->lt($cutoffDate)) {
                return ['valid' => false, 'reason' => 'Expired RO'];
            }
        }

        return ['valid' => true];
    }

    /**
     * Generate CSV content from meter data
     * Format: meter_description,customer_name,reading,date,time,measuring_point
     */
    protected function generateCsvContent(array $meters): string
    {
        $lines = [];

        foreach ($meters as $meter) {
            $readingTime = Carbon::parse($meter->latest_reading_time);
            
            $lines[] = implode(',', [
                $meter->sap_measuring_point_desc ?? $meter->meter_name,
                $meter->customer_name,
                number_format($meter->latest_reading, 2, '.', ''),
                $readingTime->format('d.m.Y'),
                $readingTime->format('H:i:s'),
                $meter->sap_measuring_point ?? '000000000000',
            ]);
        }

        return implode("\n", $lines);
    }

    /**
     * Generate file name
     * Format: {BUSINESS_ENTITY}_{COMPANY}_{DAY}_{MONTH}_{YEAR}.csv
     */
    protected function generateFileName(Site $site, Carbon $date): string
    {
        return sprintf(
            '%s_%s_%s_%s_%s.csv',
            $site->sap_business_entity,
            $site->sap_company_code,
            $date->day,
            $date->month,
            $date->year
        );
    }

    /**
     * Get full file path
     */
    protected function getFilePath(string $fileName): string
    {
        return $this->config['export']['path'] . '/' . $fileName;
    }

    /**
     * Check if export is enabled
     */
    protected function isEnabled(): bool
    {
        return $this->config['export']['enabled'] ?? true;
    }

    /**
     * Archive exported file
     */
    public function archiveFile(string $filePath): bool
    {
        $archivePath = $this->config['export']['archive_path'];
        
        if (!is_dir($archivePath)) {
            mkdir($archivePath, 0755, true);
        }

        $fileName = basename($filePath);
        $destination = $archivePath . '/' . $fileName;

        if (file_exists($filePath) && rename($filePath, $destination)) {
            Log::info("Archived export file: {$fileName}");
            return true;
        }

        return false;
    }
}
