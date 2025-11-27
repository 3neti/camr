<?php

namespace App\Services\Sap;

use App\Models\Meter;
use App\Models\Site;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MeterMasterImporter extends SapFileImporter
{
    protected string $importType = 'meters';

    /**
     * Process a single meter row from CSV
     * 
     * CSV Format (27 columns):
     * COMPANY_CODE, BUSINESS_ENTITY, BUILDING, BUILDING DESCRIPTION, LAND, LAND DESCRIPTION,
     * RENTAL_OBJECT_NO, RENTAL_OBJECT_NAME, USAGE TYPE, RO VALID FROM, RO VALID TO,
     * CONTRACT_NUMBER, CONTRACT_NAME (TENANT_NAME), METER_CHARACTERISTIC, MEASPOINT,
     * METER DESCRIPTION, MEASUREMENT_SEQUENCE, MEASUREMENT_SEPARATOR, MEASUREMENT_MULTIPLIER,
     * METER_READING_DATE, METER_READING, METER_STATUS, PARTICIPATION GROUP,
     * CREATION_DATE, CREATION_BY, LAST_CHANGE_ON, LAST_CHANGE_BY
     */
    protected function processRow(string $line, int $index): void
    {
        $cols = $this->parseRow($line);
        
        // Skip header row
        if ($index === 0 && strtoupper($cols[0] ?? '') === 'COMPANY_CODE') {
            $this->skippedRows++;
            return;
        }

        // Validate minimum columns
        if (count($cols) < 27) {
            throw new \Exception("Invalid column count: " . count($cols) . ", expected 27");
        }

        // Extract and clean data
        $companyCode = trim($cols[0]);
        $businessEntity = trim($cols[1]);
        $building = trim($cols[2]);
        $buildingDescription = trim($cols[3]);
        $land = trim($cols[4]);
        $landDescription = trim($cols[5]);
        $rentalObject = trim($cols[6]);
        $rentalObjectName = trim($cols[7]);
        $usageType = trim($cols[8]);
        $roValidFrom = trim($cols[9]);
        $roValidTo = trim($cols[10]);
        $contractNumber = trim($cols[11]);
        $contractName = trim($cols[12]);
        $meterCharacteristic = trim($cols[13]);
        $measuringPoint = trim($cols[14]);
        $meterDescription = trim($cols[15]);
        $measurementSequence = trim($cols[16]);
        $measurementSeparator = trim($cols[17]);
        $meterMultiplier = trim($cols[18]);
        $meterReadingDate = trim($cols[19]);
        $meterReading = trim($cols[20]);
        $meterStatus = trim($cols[21]);
        $participationGroup = trim($cols[22]);
        $creationDate = trim($cols[23]);
        $creationBy = trim($cols[24]);
        $lastChangeOn = trim($cols[25]);
        $lastChangeBy = trim($cols[26] ?? '');
        
        // Clean newline characters from last column
        $lastChangeBy = str_replace(["\r\n", "\r", "\n"], '', $lastChangeBy);

        // Validate meter description is numeric
        $meterDescriptionTrimmed = trim($meterDescription);
        if (empty($meterDescriptionTrimmed) || !is_numeric($meterDescriptionTrimmed)) {
            Log::warning("Invalid meter description: {$meterDescription}");
            $this->skippedRows++;
            return;
        }

        // Get or find site by business entity
        $site = Site::where('sap_business_entity', $businessEntity)->first();
        
        if (!$site) {
            // Create a placeholder site if it doesn't exist
            Log::warning("Site not found for business entity: {$businessEntity}, creating placeholder");
            $site = Site::firstOrCreate(
                ['code' => $businessEntity],
                [
                    'name' => $businessEntity,
                    'company_id' => 1, // Default company
                    'division_id' => 1, // Default division
                    'sap_business_entity' => $businessEntity,
                    'sap_company_code' => $companyCode,
                ]
            );
        }

        // Determine meter role based on customer name
        $meterRole = empty($contractName) ? 'Spare Meter' : 'Client Meter';
        
        // Determine status based on customer name
        $status = empty($contractName) ? 'Inactive' : $this->mapMeterStatus($meterStatus);

        // Handle meter multiplier (default to 1 if empty or zero)
        $multiplier = empty($meterMultiplier) || $meterMultiplier == 0 ? 1.00 : (float)$meterMultiplier;

        // Parse dates
        $roValidFromDate = $this->parseDate($roValidFrom);
        $roValidToDate = $this->parseDate($roValidTo);
        $creationDateParsed = $this->parseDate($creationDate);
        $lastChangeOnParsed = $this->parseDate($lastChangeOn);

        // Prepare meter data
        $meterData = [
            'name' => $meterDescriptionTrimmed,
            'site_id' => $site->id,
            'site_code' => $site->code,
            'role' => $meterRole,
            'status' => $status,
            'customer_name' => $contractName,
            'multiplier' => $multiplier,
            'remarks' => null,
            
            // SAP specific fields
            'sap_company_code' => $companyCode,
            'sap_business_entity' => $businessEntity,
            'sap_building' => $building,
            'sap_building_description' => $buildingDescription,
            'sap_land' => $land,
            'sap_land_description' => $landDescription,
            'sap_rental_object' => $rentalObject,
            'sap_rental_object_name' => $rentalObjectName,
            'sap_usage_type' => $usageType,
            'sap_ro_valid_from' => $roValidFromDate,
            'sap_ro_valid_to' => $roValidToDate,
            'sap_contract_number' => $contractNumber,
            'sap_meter_characteristic' => $meterCharacteristic,
            'sap_measuring_point' => $measuringPoint,
            'sap_measuring_point_desc' => $meterDescription,
            'sap_measurement_sequence' => !empty($measurementSequence) ? (int)$measurementSequence : null,
            'sap_measurement_separator' => $measurementSeparator,
            'sap_participation_group' => $participationGroup,
            'sap_creation_date' => $creationDateParsed,
            'sap_creation_by' => $creationBy,
            'sap_last_change_on' => $lastChangeOnParsed,
            'sap_last_change_by' => $lastChangeBy,
            'sap_source' => $this->log->source,
        ];

        // Check if meter exists (by name and business entity)
        $existingMeter = Meter::where('name', $meterDescriptionTrimmed)
            ->where('sap_business_entity', $businessEntity)
            ->first();

        if (!$existingMeter) {
            // Check if meter should be inserted (only active meters or based on legacy logic)
            if ($this->shouldInsertMeter($meterData)) {
                // Need to assign a gateway - find first gateway for this site or create unassigned
                $gateway = $site->gateways()->first();
                
                if (!$gateway) {
                    Log::warning("No gateway found for site {$site->code}, meter will need manual gateway assignment");
                    $this->skippedRows++;
                    return;
                }

                $meterData['gateway_id'] = $gateway->id;
                
                Meter::create($meterData);
                $this->insertedRows++;
            } else {
                $this->skippedRows++;
            }
        } else {
            // Update existing meter - check if we should update based on measuring point
            if ($this->shouldUpdateMeter($existingMeter, $measuringPoint)) {
                $existingMeter->update($meterData);
                $this->updatedRows++;
            } else {
                $this->skippedRows++;
            }
        }
    }

    /**
     * Determine if meter should be inserted
     */
    protected function shouldInsertMeter(array $meterData): bool
    {
        // Insert if active with customer name (following legacy logic)
        return $meterData['status'] === 'Active' && !empty($meterData['customer_name']);
    }

    /**
     * Determine if meter should be updated
     */
    protected function shouldUpdateMeter(Meter $meter, string $newMeasuringPoint): bool
    {
        // Update if new measuring point is greater than or equal to existing
        // This follows the legacy script logic
        $existingPoint = (int)($meter->sap_measuring_point ?? 0);
        $newPoint = (int)$newMeasuringPoint;
        
        return $newPoint >= $existingPoint;
    }

    /**
     * Map SAP meter status to CAMR status
     */
    protected function mapMeterStatus(string $sapStatus): string
    {
        $mapping = config('sap.mapping.meter_status');
        $status = strtoupper(trim($sapStatus));
        
        return $mapping[$status] ?? 'Inactive';
    }

    /**
     * Parse SAP date format (YYYYMMDD) to Carbon instance
     */
    protected function parseDate(?string $date): ?Carbon
    {
        if (empty($date) || $date === '00000000' || $date === '99991231') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Ymd', $date);
        } catch (\Exception $e) {
            Log::warning("Failed to parse date: {$date}");
            return null;
        }
    }

    /**
     * Cleanup after import - delete unassigned inactive meters
     */
    public function import(): array
    {
        $result = parent::import();

        if ($result['success'] && config('sap.import.cleanup_unassigned_inactive')) {
            $deleted = Meter::where('site_code', 'unassigned')
                ->where('status', 'Inactive')
                ->delete();
                
            if ($deleted > 0) {
                Log::info("Cleaned up {$deleted} unassigned inactive meters");
            }
        }

        return $result;
    }
}
