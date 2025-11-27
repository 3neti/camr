<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\ConfigurationFile;
use App\Models\Division;
use App\Models\Gateway;
use App\Models\ImportJob;
use App\Models\Meter;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\SqlDumpParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProcessSqlDumpJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600; // 1 hour timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importJobId,
        public string $filePath,
        public array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importJob = ImportJob::find($this->importJobId);
        if (!$importJob) {
            return;
        }

        try {
            // Update status
            $importJob->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Get full path
            $fullPath = Storage::path($this->filePath);

            // Parse SQL dump
            $parser = new SqlDumpParser($fullPath);
            $parser->parse();

            // Get statistics
            $stats = $parser->getStatistics();
            $totalRecords = array_sum(array_column($stats, 'rows'));

            $importJob->update(['total_records' => $totalRecords]);

            $result = [
                'sites_imported' => 0,
                'users_imported' => 0,
                'gateways_imported' => 0,
                'meters_imported' => 0,
            ];

            // Process in transaction
            DB::transaction(function () use ($parser, $importJob, &$result) {
                // Import sites
                $result['sites_imported'] = $this->importSites($parser);
                
                // Import users  
                $result['users_imported'] = $this->importUsers($parser);
                
                // Import gateways
                $result['gateways_imported'] = $this->importGateways($parser);
                
                // Import meters
                $result['meters_imported'] = $this->importMeters($parser);
            });

            // Success
            $importJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'result' => $result,
                'processed_records' => $importJob->total_records,
            ]);

        } catch (\Exception $e) {
            // Failure
            $importJob->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function importSites(SqlDumpParser $parser): int
    {
        $rows = $parser->getTableRows('meter_site');
        if (empty($rows)) return 0;

        $company = Company::firstOrCreate(['name' => 'Default Company']);
        $division = Division::firstOrCreate(['code' => 'DEFAULT', 'name' => 'Default Division']);

        $imported = 0;
        foreach ($rows as $row) {
            $code = $row['site_code'] ?? ($row['site_name'] ?? null);
            if (!$code) continue;

            Site::firstOrCreate(['code' => $code], [
                'company_id' => $company->id,
                'division_id' => $division->id,
            ]);
            $imported++;
        }

        return $imported;
    }

    private function importUsers(SqlDumpParser $parser): int
    {
        $rows = $parser->getTableRows('user_tb');
        if (empty($rows)) return 0;

        $imported = 0;
        foreach ($rows as $row) {
            $emailOrName = $row['user_name'] ?? null;
            if (!$emailOrName) continue;

            $email = filter_var($emailOrName, FILTER_VALIDATE_EMAIL) 
                ? $emailOrName 
                : ($emailOrName . '@example.com');

            User::firstOrCreate(['email' => $email], [
                'name' => $row['user_real_name'] ?? $emailOrName,
                'password' => Hash::make('password'),
            ]);
            $imported++;
        }

        return $imported;
    }

    private function importGateways(SqlDumpParser $parser): int
    {
        $rows = $parser->getTableRows('meter_rtu');
        if (empty($rows)) return 0;

        $imported = 0;
        foreach ($rows as $row) {
            $serial = $row['rtu_sn_number'] ?? null;
            if (!$serial) continue;

            $siteCode = $row['rtu_site_name'] ?? null;
            $site = $siteCode ? Site::where('code', $siteCode)->first() : Site::first();
            if (!$site) continue;

            Gateway::updateOrCreate(['serial_number' => $serial], [
                'site_id' => $site->id,
                'site_code' => $site->code,
                'mac_address' => $row['mac_addr'] ?? null,
                'ip_address' => $row['phone_no_or_ip_address'] ?? null,
                'connection_type' => 'LAN',
                'software_version' => $row['soft_rev'] ?? null,
            ]);
            $imported++;
        }

        return $imported;
    }

    private function importMeters(SqlDumpParser $parser): int
    {
        $rows = $parser->getTableRows('meter_details');
        if (empty($rows)) return 0;

        $imported = 0;
        foreach ($rows as $row) {
            $name = $row['meter_name'] ?? null;
            if (!$name) continue;

            $siteCode = $row['meter_site_name'] ?? 'DEFAULT';
            $site = Site::firstOrCreate(['code' => $siteCode], [
                'company_id' => Company::firstOrCreate(['name' => 'Default Company'])->id,
                'division_id' => Division::firstOrCreate(['code' => 'DEFAULT', 'name' => 'Default Division'])->id,
            ]);

            $gatewaySerial = $row['rtu_sn_number'] ?? null;
            $gateway = $gatewaySerial 
                ? Gateway::where('serial_number', $gatewaySerial)->first() 
                : Gateway::first();
            if (!$gateway) continue;

            // Handle configuration file (meter_config_file in legacy system)
            $configurationFileId = null;
            $configFile = $row['meter_config_file'] ?? ($row['meter_model'] ?? null);
            
            if ($configFile && str_ends_with($configFile, '.cfg')) {
                // This is a config file, create or find ConfigurationFile record
                $configFileRecord = ConfigurationFile::firstOrCreate(
                    ['meter_model' => $configFile],
                    [
                        'config_file_content' => '', // Empty string as placeholder
                        'created_by' => null,
                        'updated_by' => null,
                    ]
                );
                $configurationFileId = $configFileRecord->id;
            }

            Meter::updateOrCreate(['name' => $name, 'site_id' => $site->id, 'gateway_id' => $gateway->id], [
                'site_code' => $site->code,
                'type' => $row['meter_type'] ?? null,
                'brand' => null, // Brand should be actual manufacturer (Schneider, ABB, etc.), not config file
                'configuration_file_id' => $configurationFileId,
                'default_name' => $row['meter_default_name'] ?? null,
                'is_addressable' => (int)($row['meter_name_addressable'] ?? 1) === 1,
                'has_load_profile' => (($row['meter_load_profile'] ?? 'NO') === 'YES'),
                'role' => $row['meter_role'] ?? 'Client Meter',
                'customer_name' => $row['customer_name'] ?? null,
                'multiplier' => (float)($row['meter_multiplier'] ?? 1),
                'status' => ($row['meter_status'] ?? 'Active') === 'INACTIVE' ? 'Inactive' : 'Active',
            ]);
            $imported++;
        }

        return $imported;
    }
}
