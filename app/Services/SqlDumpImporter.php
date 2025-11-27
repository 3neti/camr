<?php

namespace App\Services;

use App\Models\MeterData;
use App\Models\Meter;
use App\Models\Site;
use App\Models\Gateway;
use App\Models\Company;
use App\Models\Division;
use App\Models\User;
use App\Models\DataImport;
use App\Models\ConfigurationFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SqlDumpImporter
{
    private DataImport $dataImport;
    private SqlDumpParser $parser;
    private array $statistics = [
        'sites' => 0,
        'users' => 0,
        'gateways' => 0,
        'meters' => 0,
        'meter_data' => 0,
    ];

    public function __construct(DataImport $dataImport)
    {
        $this->dataImport = $dataImport;
    }

    /**
     * Import SQL dump file
     */
    public function import(): void
    {
        try {
            $this->dataImport->markAsProcessing();

            if (!file_exists($this->dataImport->file_path)) {
                throw new \Exception("File not found: {$this->dataImport->file_path}");
            }

            $this->parser = new SqlDumpParser($this->dataImport->file_path);
            $this->parser->parse();

            // Import in dependency order
            DB::transaction(function () {
                $this->importSites();
                $this->importUsers();
                $this->importGateways();
                $this->importMeters();
                $this->importMeterData();
            });

            $this->dataImport->markAsCompleted($this->statistics);
            Log::info('SQL dump import completed', $this->statistics);

            // Clean up file
            $this->cleanup();
        } catch (\Exception $e) {
            Log::error('SQL dump import failed', [
                'error' => $e->getMessage(),
                'file' => $this->dataImport->file_path,
            ]);

            $this->dataImport->markAsFailed($e->getMessage());
            $this->cleanup();

            throw $e;
        }
    }

    /**
     * Import sites
     */
    private function importSites(): void
    {
        $rows = $this->parser->getTableRows('meter_site');

        if (empty($rows)) {
            Log::warning('No meter_site found in dump');
            return;
        }

        $company = Company::firstOrCreate(['name' => 'Default Company']);
        $division = Division::firstOrCreate(['code' => 'DEFAULT', 'name' => 'Default Division']);

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

            $this->statistics['sites']++;
            $this->dataImport->updateProgress($this->statistics['sites'], count($rows), 0);
        }

        Log::info("Imported {$this->statistics['sites']} sites");
    }

    /**
     * Import users
     */
    private function importUsers(): void
    {
        $rows = $this->parser->getTableRows('user_tb');

        if (empty($rows)) {
            Log::warning('No user_tb found in dump');
            return;
        }

        $roleMap = [
            'Admin' => 'admin',
            'BA' => 'admin',
            'OM' => 'user',
            'RE' => 'user',
        ];

        foreach ($rows as $row) {
            $emailOrName = $row['user_name'] ?? null;
            if (!$emailOrName) continue;

            $email = filter_var($emailOrName, FILTER_VALIDATE_EMAIL)
                ? $emailOrName
                : ($emailOrName . '@example.com');

            User::firstOrCreate([
                'email' => $email,
            ], [
                'name' => $row['user_real_name'] ?? $emailOrName,
                'password' => bcrypt('password'),
            ]);

            $this->statistics['users']++;
            $this->dataImport->updateProgress($this->statistics['users'], count($rows), 0);
        }

        Log::info("Imported {$this->statistics['users']} users");
    }

    /**
     * Import gateways
     */
    private function importGateways(): void
    {
        $rows = $this->parser->getTableRows('meter_rtu');

        if (empty($rows)) {
            Log::warning('No meter_rtu found in dump');
            return;
        }

        foreach ($rows as $row) {
            $serial = $row['rtu_sn_number'] ?? null;
            $mac = $row['mac_addr'] ?? null;
            $ip = $row['phone_no_or_ip_address'] ?? null;

            if (!$serial || !$mac || !$ip) continue;

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

            $this->statistics['gateways']++;
            $this->dataImport->updateProgress($this->statistics['gateways'], count($rows), 0);
        }

        Log::info("Imported {$this->statistics['gateways']} gateways");
    }

    /**
     * Import meters
     */
    private function importMeters(): void
    {
        $rows = $this->parser->getTableRows('meter_details');

        if (empty($rows)) {
            Log::warning('No meter_details found in dump');
            return;
        }

        foreach ($rows as $row) {
            $name = $row['meter_name'] ?? null;
            if (!$name) continue;

            $siteCode = $row['meter_site_name'] ?? ($row['company_no'] ?? 'DEFAULT');
            $site = Site::firstOrCreate(['code' => $siteCode], [
                'company_id' => Company::firstOrCreate(['name' => 'Default Company'])->id,
                'division_id' => Division::firstOrCreate(['code' => 'DEFAULT', 'name' => 'Default Division'])->id,
            ]);

            $gatewaySerial = $row['rtu_sn_number'] ?? null;
            $gateway = $gatewaySerial ? Gateway::where('serial_number', $gatewaySerial)->first() : Gateway::first();

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

            Meter::updateOrCreate([
                'name' => $name,
                'site_id' => $site->id,
                'gateway_id' => $gateway->id,
            ], [
                'site_code' => $site->code,
                'is_addressable' => (int)($row['meter_name_addressable'] ?? 1) === 1,
                'has_load_profile' => (($row['meter_load_profile'] ?? 'NO') === 'YES'),
                'type' => $row['meter_type'] ?? null,
                'brand' => null, // Brand should be actual manufacturer (Schneider, ABB, etc.), not config file
                'configuration_file_id' => $configurationFileId,
                'default_name' => $row['meter_default_name'] ?? null,
                'role' => $row['meter_role'] ?? 'Client Meter',
                'customer_name' => $row['customer_name'] ?? null,
                'multiplier' => (float)($row['meter_multiplier'] ?? 1),
                'status' => ($row['meter_status'] ?? 'Active') === 'INACTIVE' ? 'Inactive' : 'Active',
                'last_log_update' => $this->parseDateTime($row['last_log_update'] ?? null),
                'software_version' => $row['soft_rev'] ?? null,
            ]);

            $this->statistics['meters']++;
            $this->dataImport->updateProgress($this->statistics['meters'], count($rows), 0);
        }

        Log::info("Imported {$this->statistics['meters']} meters");
    }

    /**
     * Import meter data
     */
    private function importMeterData(): void
    {
        $rows = $this->parser->getTableRows('meter_data');

        if (empty($rows)) {
            Log::warning('No meter_data found in dump');
            return;
        }

        $recentRows = array_filter($rows, function ($row) {
            return isset($row['datetime']) && $row['datetime'] !== '0000-00-00 00:00:00';
        });

        $total = count($recentRows);
        $imported = 0;
        $errors = 0;

        foreach ($recentRows as $index => $row) {
            try {
                $data = $this->mapMeterDataRow($row);

                if ($data) {
                    MeterData::create($data);
                    $imported++;
                } else {
                    $errors++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::warning("Failed to import meter data row", ['error' => $e->getMessage()]);
            }

            // Update progress every 100 records
            if (($index + 1) % 100 === 0) {
                $this->dataImport->updateProgress($imported, $total, $errors);
            }
        }

        $this->dataImport->updateProgress($imported, $total, $errors);
        $this->statistics['meter_data'] = $imported;

        Log::info("Imported {$imported}/{$total} meter readings (errors: {$errors})");
    }

    /**
     * Map meter data row to new structure
     */
    private function mapMeterDataRow(array $row): ?array
    {
        if (!isset($row['datetime']) || $row['datetime'] === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            $datetime = Carbon::parse($row['datetime']);
        } catch (\Exception $e) {
            return null;
        }

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

            // Power
            'frequency' => $this->parseFloat($row['freq'] ?? null),
            'power_factor' => $this->parseFloat($row['pf'] ?? null),
            'watt' => $this->parseFloat($row['watt'] ?? null),
            'va' => $this->parseFloat($row['va'] ?? null),
            'var' => $this->parseFloat($row['var'] ?? null),

            // Energy
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

            // Demand
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
        ];
    }

    /**
     * Parse float value
     */
    private function parseFloat($value): ?float
    {
        if ($value === null || $value === '' || $value === 'NULL') {
            return null;
        }

        return (float)$value;
    }

    /**
     * Parse datetime value
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
     * Clean up temporary file
     */
    private function cleanup(): void
    {
        try {
            if (file_exists($this->dataImport->file_path)) {
                unlink($this->dataImport->file_path);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to clean up import file", ['error' => $e->getMessage()]);
        }
    }
}
