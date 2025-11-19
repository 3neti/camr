<?php

namespace Database\Seeders;

use App\Models\MeterData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * CSV Meter Data Seeder
 * 
 * Imports fresh meter readings from CSV files exported from the live system.
 * These CSV files contain the most recent data.
 */
class CsvMeterDataSeeder extends Seeder
{
    private string $csvDirectory;
    
    public function __construct()
    {
        $this->csvDirectory = env('CSV_DATA_PATH', '/Users/rli/Documents/DEC/backup/csv');
    }
    
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('📂 Importing meter data from CSV files...');
        $this->command->info("   Directory: {$this->csvDirectory}");
        
        if (!is_dir($this->csvDirectory)) {
            $this->command->error("❌ Directory not found: {$this->csvDirectory}");
            return;
        }
        
        // Find all CSV files
        $csvFiles = glob($this->csvDirectory . '/*.csv');
        
        if (empty($csvFiles)) {
            $this->command->warn('⚠️  No CSV files found in directory');
            return;
        }
        
        $this->command->info('   Found ' . count($csvFiles) . ' CSV file(s)');
        
        $totalImported = 0;
        $totalSkipped = 0;
        
        foreach ($csvFiles as $csvFile) {
            $fileName = basename($csvFile);
            $this->command->info("\n📄 Processing: {$fileName}");
            
            [$imported, $skipped] = $this->importCsvFile($csvFile);
            
            $totalImported += $imported;
            $totalSkipped += $skipped;
        }
        
        $this->command->newLine();
        $this->command->info("✅ CSV import completed!");
        $this->command->info("   Total imported: {$totalImported}");
        if ($totalSkipped > 0) {
            $this->command->warn("   Total skipped: {$totalSkipped}");
        }
    }
    
    /**
     * Import a single CSV file
     */
    private function importCsvFile(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->command->error("   ❌ Could not open file");
            return [0, 0];
        }
        
        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $this->command->error("   ❌ Could not read header");
            return [0, 0];
        }
        
        // Count total rows for progress bar
        $totalRows = 0;
        while (fgets($handle) !== false) {
            $totalRows++;
        }
        rewind($handle);
        fgetcsv($handle); // Skip header again
        
        $this->command->info("   Rows: {$totalRows}");
        
        $progressBar = $this->command->getOutput()->createProgressBar($totalRows);
        $progressBar->start();
        
        $imported = 0;
        $skipped = 0;
        $batch = [];
        $batchSize = 500;
        
        while (($row = fgetcsv($handle)) !== false) {
            try {
                // Map CSV row to associative array
                $data = array_combine($header, $row);
                
                // Map to database structure
                $meterData = $this->mapCsvRow($data);
                
                if ($meterData) {
                    $batch[] = $meterData;
                    
                    // Insert in batches for better performance
                    if (count($batch) >= $batchSize) {
                        DB::table('meter_data')->insert($batch);
                        $imported += count($batch);
                        $batch = [];
                    }
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $skipped++;
            }
            
            $progressBar->advance();
        }
        
        // Insert remaining batch
        if (!empty($batch)) {
            DB::table('meter_data')->insert($batch);
            $imported += count($batch);
        }
        
        $progressBar->finish();
        $this->command->newLine();
        $this->command->info("   ✓ Imported: {$imported}");
        if ($skipped > 0) {
            $this->command->warn("   ⚠ Skipped: {$skipped}");
        }
        
        fclose($handle);
        
        return [$imported, $skipped];
    }
    
    /**
     * Map CSV row to database structure
     */
    private function mapCsvRow(array $row): ?array
    {
        // Skip invalid datetimes
        if (!isset($row['datetime']) || $row['datetime'] === '0000-00-00 00:00:00' || empty($row['datetime'])) {
            return null;
        }
        
        try {
            $datetime = Carbon::parse($row['datetime']);
        } catch (\Exception $e) {
            return null;
        }
        
        // Skip if meter_id is missing
        if (empty($row['meter_id'])) {
            return null;
        }
        
        return [
            'location' => $row['location'] ?? 'Unknown',
            'meter_name' => $row['meter_id'],
            'reading_datetime' => $datetime,
            
            // Voltage
            'vrms_a' => $this->parseFloat($row['vrms_a'] ?? null),
            'vrms_b' => $this->parseFloat($row['vrms_b'] ?? null),
            'vrms_c' => $this->parseFloat($row['vrms_c'] ?? null),
            
            // Current
            'irms_a' => $this->parseFloat($row['irms_a'] ?? null),
            'irms_b' => $this->parseFloat($row['irms_b'] ?? null),
            'irms_c' => $this->parseFloat($row['irms_c'] ?? null),
            
            // Power measurements
            'frequency' => $this->parseFloat($row['freq'] ?? null),
            'power_factor' => $this->parseFloat($row['pf'] ?? null),
            'watt' => $this->parseFloat($row['watt'] ?? null),
            'va' => $this->parseFloat($row['va'] ?? null),
            'var' => $this->parseFloat($row['var'] ?? null),
            
            // Energy measurements
            'wh_delivered' => $this->parseFloat($row['wh_del'] ?? null),
            'wh_received' => $this->parseFloat($row['wh_rec'] ?? null),
            'wh_net' => $this->parseFloat($row['wh_net'] ?? null),
            'wh_total' => $this->parseFloat($row['wh_total'] ?? null),
            
            // Reactive energy
            'varh_negative' => $this->parseFloat($row['varh_neg'] ?? null),
            'varh_positive' => $this->parseFloat($row['varh_pos'] ?? null),
            'varh_net' => $this->parseFloat($row['varh_net'] ?? null),
            'varh_total' => $this->parseFloat($row['varh_total'] ?? null),
            
            // Apparent energy
            'vah_total' => $this->parseFloat($row['vah_total'] ?? null),
            
            // Demand measurements
            'max_rec_kw_demand' => $this->parseFloat($row['max_rec_kw_dmd'] ?? null),
            'max_rec_kw_demand_time' => $this->parseDateTime($row['max_rec_kw_dmd_time'] ?? null),
            'max_del_kw_demand' => $this->parseFloat($row['max_del_kw_dmd'] ?? null),
            'max_del_kw_demand_time' => $this->parseDateTime($row['max_del_kw_dmd_time'] ?? null),
            'max_pos_kvar_demand' => $this->parseFloat($row['max_pos_kvar_dmd'] ?? null),
            'max_pos_kvar_demand_time' => $this->parseDateTime($row['max_pos_kvar_dmd_time'] ?? null),
            'max_neg_kvar_demand' => $this->parseFloat($row['max_neg_kvar_dmd'] ?? null),
            'max_neg_kvar_demand_time' => $this->parseDateTime($row['max_neg_kvar_dmd_time'] ?? null),
            
            // Phase angles
            'v_phase_angle_a' => $this->parseFloat($row['v_ph_angle_a'] ?? null),
            'v_phase_angle_b' => $this->parseFloat($row['v_ph_angle_b'] ?? null),
            'v_phase_angle_c' => $this->parseFloat($row['v_ph_angle_c'] ?? null),
            'i_phase_angle_a' => $this->parseFloat($row['i_ph_angle_a'] ?? null),
            'i_phase_angle_b' => $this->parseFloat($row['i_ph_angle_b'] ?? null),
            'i_phase_angle_c' => $this->parseFloat($row['i_ph_angle_c'] ?? null),
            
            // Metadata
            'mac_address' => $row['mac_addr'] ?? null,
            'software_version' => $row['soft_rev'] ?? null,
            'relay_status' => isset($row['relay_status']) && $row['relay_status'] !== 'NULL' ? (bool)$row['relay_status'] : null,
            'genset_status' => isset($row['genset_status']) && $row['genset_status'] !== 'NULL' ? (bool)$row['genset_status'] : null,
            
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    /**
     * Parse float value
     */
    private function parseFloat(?string $value): ?float
    {
        if ($value === null || $value === '' || $value === 'NULL') {
            return null;
        }
        
        $float = (float) $value;
        return $float == 0 ? null : $float;
    }
    
    /**
     * Parse datetime value
     */
    private function parseDateTime(?string $value): ?Carbon
    {
        if (!$value || $value === '0000-00-00 00:00:00' || $value === '0000-00-00' || $value === 'NULL') {
            return null;
        }
        
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
