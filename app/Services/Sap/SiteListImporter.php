<?php

namespace App\Services\Sap;

use App\Models\Site;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SiteListImporter extends SapFileImporter
{
    protected string $importType = 'sites';

    /**
     * Process a single site row from CSV
     * 
     * CSV Format (13 columns):
     * COMPANY_CODE, BUSINESS_ENTITY, SERVICE_CHARGE_KEY, PARTICIPATION GROUP,
     * SETTLEMENT_UNIT, METER_READING_CUTOFF, SETTLEMENT VARIANT TEXT,
     * SETTLEMENT VALID FROM, SETTLEMENT VALID TO, CREATED_ON, CREATED_AT,
     * LAST_EDITED_ON, LAST_EDITED_AT
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
        if (count($cols) < 13) {
            throw new \Exception("Invalid column count: " . count($cols) . ", expected 13");
        }

        // Extract and clean data
        $companyCode = trim($cols[0]);
        $businessEntity = trim($cols[1]);
        $serviceChargeKey = trim($cols[2]);
        $participationGroup = trim($cols[3]);
        $settlementUnit = trim($cols[4]);
        $meterReadingCutoff = trim($cols[5]);
        $settlementVariantText = trim($cols[6]);
        $settlementValidFrom = trim($cols[7]);
        $settlementValidTo = trim($cols[8]);
        $createdOn = trim($cols[9]);
        $createdAt = trim($cols[10]);
        $lastEditedOn = trim($cols[11]);
        $lastEditedAt = trim($cols[12]);

        // Skip if this is the actual header row content
        if ($companyCode === 'COMPANY_CODE') {
            $this->skippedRows++;
            return;
        }

        // Validate settlement validity dates and calculate final cut-off
        $cutoffDay = $this->calculateCutoffDay($meterReadingCutoff, $settlementValidTo);

        // Parse dates
        $validFromDate = $this->parseDate($settlementValidFrom);
        $validToDate = $this->parseDate($settlementValidTo);

        // Prepare site data
        $siteData = [
            'sap_company_code' => $companyCode,
            'sap_business_entity' => $businessEntity,
            'sap_cut_off_day' => $cutoffDay,
            'sap_service_charge_key' => $serviceChargeKey,
            'sap_participation_group' => $participationGroup,
            'sap_settlement_unit' => $settlementUnit,
            'sap_settlement_variant' => $settlementVariantText,
            'sap_settlement_valid_from' => $validFromDate,
            'sap_settlement_valid_to' => $validToDate,
            'sap_source' => $this->log->source,
        ];

        // Check if site exists by business entity
        $existingSite = Site::where('sap_business_entity', $businessEntity)->first();

        if (!$existingSite) {
            // Check if site exists by code (business entity might be the code)
            $existingSite = Site::where('code', $businessEntity)->first();
        }

        if (!$existingSite) {
            // Create new site
            $siteData = array_merge($siteData, [
                'code' => $businessEntity,
                'name' => $businessEntity,
                'company_id' => 1, // Default company
                'division_id' => 1, // Default division
            ]);

            Site::create($siteData);
            $this->insertedRows++;
            
            Log::info("Created new site: {$businessEntity}");
        } else {
            // Update existing site
            $existingSite->update($siteData);
            $this->updatedRows++;
            
            Log::debug("Updated site: {$businessEntity}");
        }
    }

    /**
     * Calculate cut-off day based on settlement validity
     * If settlement is expired or invalid, set cut-off to 0
     */
    protected function calculateCutoffDay(string $cutoffDay, string $validTo): int
    {
        $cutoff = (int)$cutoffDay;
        
        // Check if settlement validity is still valid
        $validToDate = $this->parseDate($validTo);
        
        if (!$validToDate) {
            return 0;
        }

        $now = Carbon::now();
        
        // If settlement has expired, set cutoff to 0
        if ($validToDate->lt($now)) {
            Log::info("Settlement expired for cutoff {$cutoffDay}, setting to 0");
            return 0;
        }

        return $cutoff;
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
}
