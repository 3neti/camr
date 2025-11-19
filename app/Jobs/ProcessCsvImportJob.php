<?php

namespace App\Jobs;

use App\Models\Gateway;
use App\Models\ImportJob;
use App\Models\Meter;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessCsvImportJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 1800; // 30 minutes

    public function __construct(
        public int $importJobId,
        public string $filePath,
        public array $options = []
    ) {}

    public function handle(): void
    {
        $importJob = ImportJob::find($this->importJobId);
        if (!$importJob) return;

        try {
            $importJob->update(['status' => 'processing', 'started_at' => now()]);

            $fullPath = Storage::path($this->filePath);
            $handle = fopen($fullPath, 'r');
            
            // Read header and count rows
            $header = fgetcsv($handle);
            $totalRows = 0;
            while (fgets($handle) !== false) $totalRows++;
            
            $importJob->update(['total_records' => $totalRows]);
            
            // Process CSV
            rewind($handle);
            fgetcsv($handle); // Skip header
            
            $imported = 0;
            $batch = [];
            $batchSize = 500;
            
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                
                // Validate required fields
                if (empty($data['meter_id']) || empty($data['datetime']) || $data['datetime'] === '0000-00-00 00:00:00') {
                    continue;
                }
                
                // Auto-create meter if option enabled
                if ($this->options['create_missing_meters'] ?? false) {
                    $this->ensureMeterExists($data['meter_id'], $data['location'] ?? 'Unknown');
                }
                
                $batch[] = [
                    'location' => $data['location'] ?? 'Unknown',
                    'meter_name' => $data['meter_id'],
                    'reading_datetime' => Carbon::parse($data['datetime']),
                    'wh_total' => $data['wh_total'] ?? null,
                    'wh_delivered' => $data['wh_del'] ?? null,
                    'wh_received' => $data['wh_rec'] ?? null,
                    'watt' => $data['watt'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                if (count($batch) >= $batchSize) {
                    DB::table('meter_data')->insert($batch);
                    $imported += count($batch);
                    $importJob->update(['processed_records' => $imported]);
                    $batch = [];
                }
            }
            
            if (!empty($batch)) {
                DB::table('meter_data')->insert($batch);
                $imported += count($batch);
            }
            
            fclose($handle);
            
            // Update timestamps if option enabled
            if ($this->options['update_timestamps'] ?? false) {
                $this->updateMeterTimestamps();
            }
            
            $importJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'processed_records' => $imported,
                'result' => ['records_imported' => $imported],
            ]);
            
        } catch (\Exception $e) {
            $importJob->update(['status' => 'failed', 'completed_at' => now(), 'error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function ensureMeterExists(string $meterName, string $location): void
    {
        if (Meter::where('name', $meterName)->exists()) return;
        
        $site = Site::firstOrCreate(['code' => $location]);
        $gateway = Gateway::first();
        
        if ($gateway) {
            Meter::create([
                'name' => $meterName,
                'site_id' => $site->id,
                'gateway_id' => $gateway->id,
                'site_code' => $location,
                'status' => 'Active',
                'customer_name' => 'Auto-created from CSV',
            ]);
        }
    }
    
    private function updateMeterTimestamps(): void
    {
        $meters = DB::table('meter_data')
            ->select('meter_name', DB::raw('MAX(reading_datetime) as latest'))
            ->groupBy('meter_name')
            ->get();
            
        foreach ($meters as $row) {
            Meter::where('name', $row->meter_name)->update(['last_log_update' => $row->latest]);
        }
    }
}
