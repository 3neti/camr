<?php

namespace Database\Seeders;

use App\Models\MeterData;
use App\Models\Meter;
use App\Models\Site;
use App\Models\Gateway;
use App\Models\Company;
use App\Models\Division;
use App\Models\Building;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Live Data Seeder
 * 
 * Imports real meter data from the legacy SQL dump.
 * This seeder extracts actual readings from the production backup.
 */
class LiveDataSeeder extends Seeder
{
    private SqlDumpParser $parser;
    private string $sqlDumpPath;
    
    public function __construct()
    {
        $this->sqlDumpPath = env('SQL_DUMP_PATH', '/Users/rli/Documents/DEC/backup/meter_reading/meter_reading.sql');
    }
    
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('🔄 Parsing SQL dump file...');
        
        $this->parser = new SqlDumpParser($this->sqlDumpPath);
        $this->parser->parse();
        
        // Get statistics
        $stats = $this->parser->getStatistics();
        $this->command->info('📊 Found ' . count($stats) . ' tables in dump');
        
        // Import data in proper dependency order
        DB::transaction(function () {
            $this->importSites();
            $this->importUsers();
            $this->importGateways();
            $this->importMeters();
            $this->importMeterData();
        });
        
        $this->command->newLine();
        $this->command->info('✅ Live data import completed!');
    }
    
    /**
     * Import sites (from meter_site table)
     */
    private function importSites(): void
    {
        $this->command->info('🏗️ Importing sites...');
        $rows = $this->parser->getTableRows('meter_site');
        
        if (empty($rows)) {
            $this->command->warn('   ⚠️ No meter_site found in dump');
            return;
        }
        
        // Minimal mapping: create a default company and division if not present
        $company = Company::firstOrCreate(['name' => 'Default Company']);
        $division = Division::firstOrCreate(['code' => 'DEFAULT', 'name' => 'Default Division']);
        
        $imported = 0;
        foreach ($rows as $row) {
            $code = $row['site_code'] ?? ($row['site_name'] ?? null);
            if (!$code) continue;
            
            Site::firstOrCreate([
                'code' => $code,
            ], [
                'company_id' => $company->id,
                'division_id' => $division->id,
                'last_log_update' => $this->parseDateTime($row['date_modified'] ?? null),
            ]);
            $imported++;
        }
        $this->command->info("   ✓ Sites upserted: {$imported}");
    }
    
    /**
     * Import users (from user_tb table)
     */
    private function importUsers(): void
    {
        $this->command->info('👤 Importing users...');
        $rows = $this->parser->getTableRows('user_tb');
        if (empty($rows)) {
            $this->command->warn('   ⚠️ No user_tb found in dump');
            return;
        }
        
        $roleMap = [
            'Admin' => 'admin',
            'BA' => 'admin',
            'OM' => 'user',
            'RE' => 'user',
        ];
        
        $imported = 0; $skipped = 0;
        foreach ($rows as $row) {
            $emailOrName = $row['user_name'] ?? null;
            if (!$emailOrName) { $skipped++; continue; }
            
            $email = filter_var($emailOrName, FILTER_VALIDATE_EMAIL) ? $emailOrName : ($emailOrName . '@example.com');
            
            User::firstOrCreate([
                'email' => $email,
            ], [
                'name' => $row['user_real_name'] ?? $emailOrName,
                'password' => Hash::make('password'),
            ]);
            $imported++;
        }
        $this->command->info("   ✓ Users upserted: {$imported}");
        if ($skipped) $this->command->warn("   ⚠ Skipped users: {$skipped}");
    }
    
    /**
     * Import gateways (from meter_rtu table)
     */
    private function importGateways(): void
    {
        $this->command->info('🌐 Importing gateways...');
        $rows = $this->parser->getTableRows('meter_rtu');
        if (empty($rows)) {
            $this->command->warn('   ⚠️ No meter_rtu found in dump');
            return;
        }
        
        $imported = 0;
        foreach ($rows as $row) {
            $serial = $row['rtu_sn_number'] ?? null;
            $mac = $row['mac_addr'] ?? null;
            $ip = $row['phone_no_or_ip_address'] ?? null;
            if (!$serial || !$mac || !$ip) continue;
            
            // Map to a site if possible
            $siteCode = $row['rtu_site_name'] ?? null;
            $site = $siteCode ? Site::where('code', $siteCode)->first() : Site::first();
            if (!$site) continue;
            
            Gateway::updateOrCreate([
                'serial_number' => $serial,
            ], [
                'site_id' => $site->id,
                'site_code' => $site->code,
                'mac_address' => $mac,
                'ip_address' => $ip,
                'connection_type' => 'LAN',
                'software_version' => $row['soft_rev'] ?? null,
                'last_log_update' => $this->parseDateTime($row['last_log_update'] ?? null),
            ]);
            $imported++;
        }
        $this->command->info("   ✓ Gateways upserted: {$imported}");
    }
    
    /**
     * Import meters (from meter_details table)
     */
    private function importMeters(): void
    {
        $this->command->info('🔌 Importing meters...');
        $rows = $this->parser->getTableRows('meter_details');
        if (empty($rows)) {
            $this->command->warn('   ⚠️ No meter_details found in dump');
            return;
        }
        
        $imported = 0; $skipped = 0;
        foreach ($rows as $row) {
            $name = $row['meter_name'] ?? null;
            if (!$name) { $skipped++; continue; }
            
            // Site
            $siteCode = $row['meter_site_name'] ?? ($row['company_no'] ?? 'DEFAULT');
            $site = Site::firstOrCreate(['code' => $siteCode], [
                'company_id' => Company::firstOrCreate(['name' => 'Default Company'])->id,
                'division_id' => Division::firstOrCreate(['code' => 'DEFAULT', 'name' => 'Default Division'])->id,
            ]);
            
            // Gateway by RTU serial
            $gatewaySerial = $row['rtu_sn_number'] ?? null;
            $gateway = $gatewaySerial ? Gateway::where('serial_number', $gatewaySerial)->first() : Gateway::first();
            if (!$gateway) { $skipped++; continue; }
            
            Meter::updateOrCreate([
                'name' => $name,
                'site_id' => $site->id,
                'gateway_id' => $gateway->id,
            ], [
                'site_code' => $site->code,
                'is_addressable' => (int)($row['meter_name_addressable'] ?? 1) === 1,
                'has_load_profile' => (($row['meter_load_profile'] ?? 'NO') === 'YES'),
                'type' => $row['meter_type'] ?? null,
                'brand' => $row['meter_model'] ?? null,
                'role' => $row['meter_role'] ?? 'Client Meter',
                'customer_name' => $row['customer_name'] ?? null,
                'multiplier' => (float)($row['meter_multiplier'] ?? 1),
                'status' => ($row['meter_status'] ?? 'Active') === 'INACTIVE' ? 'Inactive' : 'Active',
                'last_log_update' => $this->parseDateTime($row['last_log_update'] ?? null),
                'software_version' => $row['soft_rev'] ?? null,
            ]);
            $imported++;
        }
        $this->command->info("   ✓ Meters upserted: {$imported}");
        if ($skipped) $this->command->warn("   ⚠ Skipped meters: {$skipped}");
    }
    
    /**
     * Import meter_data records
     */
    private function importMeterData(): void
    {
        $this->command->info('');
        $this->command->info('📊 Importing meter_data...');
        
        $rows = $this->parser->getTableRows('meter_data');
        
        if (empty($rows)) {
            $this->command->warn('⚠️  No meter_data found in dump');
            return;
        }
        
        $this->command->info('   Found ' . count($rows) . ' meter readings');
        
        // Get date range from the dump
        $dates = array_column($rows, 'datetime');
        $validDates = array_filter($dates, fn($d) => $d && $d !== '0000-00-00 00:00:00');
        
        if (!empty($validDates)) {
            $latestDate = max($validDates);
            $earliestDate = min($validDates);
            $this->command->info("   Date range: {$earliestDate} to {$latestDate}");
        }
        
        // Import ALL data (no filtering)
        $this->command->info('   Importing ALL meter readings...');
        $recentRows = array_filter($rows, function($row) {
            return isset($row['datetime']) && $row['datetime'] !== '0000-00-00 00:00:00';
        });
        
        $this->command->info('   Processing ' . count($recentRows) . ' readings...');
        
        $progressBar = $this->command->getOutput()->createProgressBar(count($recentRows));
        $progressBar->start();
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($recentRows as $row) {
            try {
                // Map old structure to new structure
                $data = $this->mapMeterDataRow($row);
                
                if ($data) {
                    MeterData::create($data);
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $skipped++;
                // Continue on error
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->command->newLine();
        $this->command->info("   ✓ Imported: {$imported} readings");
        if ($skipped > 0) {
            $this->command->warn("   ⚠ Skipped: {$skipped} readings");
        }
    }
    
    /**
     * Map old meter_data structure to new structure
     */
    private function mapMeterDataRow(array $row): ?array
    {
        // Skip invalid datetimes
        if (!isset($row['datetime']) || $row['datetime'] === '0000-00-00 00:00:00') {
            return null;
        }
        
        try {
            $datetime = Carbon::parse($row['datetime']);
        } catch (\Exception $e) {
            return null;
        }
        
        // Map fields from old to new structure
        return [
            'location' => $row['location'] ?? 'Unknown',
            'meter_name' => $row['meter_id'] ?? 'Unknown',
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
            'relay_status' => isset($row['relay_status']) ? (bool)$row['relay_status'] : null,
            'genset_status' => isset($row['genset_status']) ? (bool)$row['genset_status'] : null,
            
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    /**
     * Parse float value, handling null and zero special cases
     */
    private function parseFloat($value): ?float
    {
        if ($value === null || $value === '' || $value === 'NULL') {
            return null;
        }
        
        return (float)$value;
    }
    
    /**
     * Parse datetime value, handling invalid dates
     */
    private function parseDateTime($value): ?Carbon
    {
        if (!$value || $value === '0000-00-00 00:00:00' || $value === 'NULL') {
            return null;
        }
        
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Export dump statistics to JSON for analysis
     */
    public function exportStatistics(): void
    {
        $this->parser = new SqlDumpParser($this->sqlDumpPath);
        $this->parser->parse();
        
        $stats = $this->parser->getStatistics();
        $outputPath = storage_path('app/dump_statistics.json');
        
        file_put_contents($outputPath, json_encode($stats, JSON_PRETTY_PRINT));
        
        $this->command->info("Statistics exported to: {$outputPath}");
    }
    
    /**
     * Export sample data for inspection
     */
    public function exportSamples(): void
    {
        $this->parser = new SqlDumpParser($this->sqlDumpPath);
        $this->parser->parse();
        
        $tables = ['meter_data', 'meter_details', 'meter_rtu', 'meter_site'];
        
        foreach ($tables as $table) {
            $sample = $this->parser->getTableSample($table, 5);
            $outputPath = storage_path("app/sample_{$table}.json");
            
            file_put_contents($outputPath, json_encode($sample, JSON_PRETTY_PRINT));
            $this->command->info("Sample exported: {$outputPath}");
        }
    }
}
