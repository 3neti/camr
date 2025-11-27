# SAP/ERP Data Integration

This document describes the SAP data integration system for bi-directional sync between SAP and CAMR.

## Overview

The SAP integration provides automated import of meter master data, site configurations, and user access lists from SAP ERP, and exports meter readings back to SAP for billing.

## Architecture

### Import Flow (SAP → CAMR)
1. SAP exports CSV files to monitored directories
2. Scheduled commands detect and process files every 5 minutes
3. Data is validated and imported into CAMR database
4. Processed files are archived
5. Import logs are stored for auditing

### Export Flow (CAMR → SAP)
1. Daily scheduled export runs at 00:15 for sites with matching cut-off days
2. Meter readings are validated against business rules
3. CSV files are generated in SAP format
4. Export logs are stored for auditing
5. Files can be archived after SAP pickup

## Configuration

### Environment Variables

Add to your `.env` file:

```env
# SAP Import Paths
SAP_IMPORT_PATH=/AMR
SAP_CHECK_SEP_FIRST=true
SAP_LOCK_PATH=/path/to/storage/app/sap/locks

# SAP Export Paths
SAP_EXPORT_PATH=/AMR/UPLOAD
SAP_EXPORT_ENABLED=true

# Feature Toggles
SAP_IMPORT_METERS_ENABLED=true
SAP_IMPORT_SITES_ENABLED=true
SAP_IMPORT_USERS_ENABLED=true
SAP_CLEANUP_UNASSIGNED=true

# Logging
SAP_LOG_LEVEL=info

# Notifications
SAP_NOTIFY_ON_ERROR=true
SAP_NOTIFICATION_EMAILS=admin@example.com
SAP_DAILY_SUMMARY=false
```

### Directory Structure

The system expects the following directory structure:

```
/AMR/
├── DOWNLOAD/               # Production SAP exports
│   ├── METER_LIST/        # Meter master files
│   ├── METER_LIST_OLD/    # Archived meter files
│   ├── SITE_LIST/         # Site/cut-off files
│   ├── SITE_LIST_OLD/     # Archived site files
│   ├── USER_LIST/         # User access files
│   └── USER_LIST_OLD/     # Archived user files
├── SEP_DOWNLOAD/          # Test/SEP environment exports
│   ├── METER_LIST/
│   ├── SITE_LIST/
│   └── USER_LIST/
└── UPLOAD/                # CAMR to SAP exports
    └── ARCHIVE/           # Archived export files
```

## Artisan Commands

### Import Commands

#### Import Meter Master Data
```bash
php artisan sap:import-meters
```
Processes meter master files from SAP containing:
- Company and business entity codes
- Building and rental object information
- Contract and customer details
- Meter characteristics and measuring points
- Status and validity dates

**File Format:** 27 columns, CSV/TSV
**Frequency:** Every 5 minutes (automated)

#### Import Site/Cut-off Data
```bash
php artisan sap:import-sites
```
Processes site configuration files from SAP containing:
- Business entity information
- Cut-off day schedules
- Settlement unit details
- Validity periods

**File Format:** 13 columns, CSV/TSV
**Frequency:** Every 5 minutes (automated)

#### Import User Access Lists
```bash
php artisan sap:import-users
```
Processes user access files from SAP containing:
- User IDs and names
- Business entity access
- Role/function assignments
- Expiration dates

**File Format:** 8 columns, CSV/TSV
**Frequency:** Every 5 minutes (automated)

### Export Commands

#### Export Meter Readings
```bash
# Export for today's cut-off day
php artisan sap:export-readings

# Export for specific cut-off day
php artisan sap:export-readings --cutoff-day=17
```

Exports meter readings to SAP format with validation:
- Only active meters with valid RO dates
- Recent readings (≤4 days old)
- Non-zero consumption values
- Valid customer and contract information

**File Format:** meter_description,customer_name,reading,date,time,measuring_point
**Frequency:** Daily at 00:15 (automated)

## Scheduled Tasks

The system automatically runs these tasks (defined in `routes/console.php`):

```php
Schedule::command('sap:import-meters')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('sap:import-sites')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('sap:import-users')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('sap:export-readings')->dailyAt('00:15')->withoutOverlapping();
```

To enable scheduling, ensure the Laravel scheduler is running:

```bash
* * * * * cd /path/to/camr && php artisan schedule:run >> /dev/null 2>&1
```

## Database Schema

### New Tables

#### `sap_import_logs`
Tracks all import operations:
- Import type, file name, source
- Row counts (total, processed, inserted, updated, skipped, errors)
- Status and duration
- Error details (JSON)

#### `sap_export_logs`
Tracks all export operations:
- Site, business entity, company code
- Cut-off date and file details
- Meter counts (total, exported, skipped)
- Validation summary (JSON)
- Status and duration

### Modified Tables

#### `meters` table
Added SAP-specific fields:
- Company and business entity information
- Building and rental object details
- Contract and measuring point data
- SAP metadata (creation date, last change)

#### `sites` table
Added SAP-specific fields:
- Company and business entity codes
- Cut-off day configuration
- Settlement unit details
- Validity periods

## File Processing

### Import Process
1. **Detection**: Checks SEP folder first (if enabled), then DOWNLOAD folder
2. **Locking**: Creates lock file to prevent concurrent processing
3. **Parsing**: Reads CSV/TSV files line by line
4. **Validation**: Validates data format and business rules
5. **Import**: Uses `updateOrCreate` for upsert logic
6. **Archiving**: Moves processed files to `*_OLD` folders
7. **Logging**: Records results in database and Laravel logs
8. **Cleanup**: Removes lock file

### Export Process
1. **Site Selection**: Finds sites with matching cut-off day
2. **Data Query**: Retrieves meters with latest readings
3. **Validation**: Applies multiple validation rules:
   - Reading exists and is recent
   - Meter is active with valid status
   - RO dates are valid
   - Customer name and contract present
   - Reading value meets minimum threshold
4. **File Generation**: Creates CSV in SAP format
5. **Logging**: Records export details in database

## Validation Rules

### Meter Import Validation
- Meter description must be numeric
- Site must exist (creates placeholder if needed)
- Gateway must be assigned
- Only inserts active meters with customer names

### Meter Export Validation
- Reading age: ≤4 days (configurable)
- Meter status: Active
- RO validity: Not expired
- Customer name: Required
- Contract number: Required
- Measuring point: Non-zero
- Reading value: ≥1.0 (configurable)
- Reading format: No exponential notation

## Monitoring

### View Import Logs
```php
use App\Models\SapImportLog;

// Recent imports
$imports = SapImportLog::latest()->take(10)->get();

// Failed imports
$failures = SapImportLog::where('status', 'failed')->get();

// Import statistics
$stats = SapImportLog::where('import_type', 'meters')
    ->selectRaw('SUM(inserted_rows) as total_inserted, SUM(updated_rows) as total_updated')
    ->first();
```

### View Export Logs
```php
use App\Models\SapExportLog;

// Recent exports
$exports = SapExportLog::with('site')->latest()->take(10)->get();

// Exports by site
$siteExports = SapExportLog::where('business_entity', 'SMCG')->get();

// Export success rate
$successRate = SapExportLog::where('status', 'success')->count() / 
               SapExportLog::count() * 100;
```

### Laravel Log
Import/export operations are logged to `storage/logs/laravel.log`:
```
[2025-11-27 14:00:00] local.INFO: Processing METER_MASTER_20251127.CSV: 1000 rows
[2025-11-27 14:00:30] local.INFO: Completed METER_MASTER_20251127.CSV: 950 inserted, 50 updated, 0 skipped, 0 errors
```

## Troubleshooting

### Import Not Running
1. Check directory permissions: `chmod 755 /AMR/DOWNLOAD`
2. Verify lock file not stuck: `rm /path/to/locks/importmetermaster`
3. Check Laravel logs: `tail -f storage/logs/laravel.log`
4. Test manually: `php artisan sap:import-meters`

### Export Failing
1. Verify site cut-off configuration
2. Check meter reading data exists
3. Review validation summary in `sap_export_logs`
4. Ensure export directory is writable

### File Not Processing
1. Verify file format (CSV/TSV)
2. Check column count matches expected
3. Validate file encoding (UTF-8)
4. Review error logs in `sap_import_logs`

## Testing

### Test with Sample Files
```bash
# Copy sample files to test directory
cp /path/to/samples/*.CSV /AMR/DOWNLOAD/METER_LIST/

# Run import manually
php artisan sap:import-meters

# Check results
php artisan tinker
>>> App\Models\SapImportLog::latest()->first()
```

### Test Export
```bash
# Export for specific day
php artisan sap:export-readings --cutoff-day=17

# Check generated files
ls -la /AMR/UPLOAD/*.csv

# View export log
php artisan tinker
>>> App\Models\SapExportLog::latest()->first()
```

## Migration Notes

When migrating from legacy PHP scripts:
1. Database schema already includes SAP fields from migrations
2. Existing meters/sites will be updated, not duplicated
3. Import logic preserves measuring point comparison
4. Export validation rules match legacy behavior
5. Lock file mechanism prevents concurrent runs
6. File archiving matches legacy folder structure

## Support

For issues or questions:
1. Check `sap_import_logs` and `sap_export_logs` tables
2. Review Laravel logs: `storage/logs/laravel.log`
3. Verify configuration in `config/sap.php`
4. Test commands manually before scheduling
5. Consult implementation plan: `docs/SAP_IMPLEMENTATION_PLAN.md`
