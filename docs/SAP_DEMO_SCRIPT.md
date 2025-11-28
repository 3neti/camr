# SAP Integration Demo Script
**Date**: November 27, 2025  
**Purpose**: Demonstrate SAP/ERP data integration to previous developer  
**Duration**: ~15-20 minutes

---

## Prerequisites Check (2 minutes)

### 1. Verify Environment Configuration
```bash
# Check .env has SAP configuration
cat .env | grep SAP_
```

**Expected Output**:
```
SAP_IMPORT_PATH="/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR"
SAP_CHECK_SEP_FIRST=false
SAP_LOCK_PATH="/Users/rli/PhpstormProjects/camr/storage/app/sap/locks"
SAP_EXPORT_PATH="/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/UPLOAD"
```

### 2. Verify Database Tables Exist
```bash
php artisan tinker
```

In Tinker:
```php
Schema::hasTable('sap_import_logs')  // Should return: true
Schema::hasTable('sap_export_logs')  // Should return: true
Schema::hasColumns('meters', ['sap_meter_number', 'sap_measuring_point'])  // Should return: true
exit
```

---

## Demo Part 1: Site Import (5 minutes)

### Step 1: Show Current State
```bash
# Count existing sites
php artisan tinker
```

In Tinker:
```php
App\Models\Site::count()  // Note this number
App\Models\Site::latest()->first()?->toArray()  // Show most recent site
exit
```

### Step 2: Show Sample SAP File
```bash
# Display sample site import file
head -20 "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD/METER_CUTOFF_20251127_TEST.CSV"
```

**Explain**: "This CSV contains site codes, names, cut-off days, and billing cycles from SAP"

### Step 3: Copy File to Import Directory
```bash
# Copy fresh test file from OLD folder
cp "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD_OLD/METER_CUTOFF_20251114_083053.CSV" \
   "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD/"
```

### Step 4: Run Site Import
```bash
php artisan sap:import-sites --verbose
```

**Expected Output**:
```
Starting SAP site import...
Found file: METER_CUTOFF_20251114_083053.CSV
Processing 3 rows...
✓ Import completed successfully
  Inserted: 1
  Updated: 1
  Skipped: 1
  Errors: 0
Archived: DOWNLOAD_OLD/METER_CUTOFF_20251114_083053_processed_YYYYMMDD_HHMMSS.CSV
```

**Explain**: 
- File was detected automatically
- Lock file prevented concurrent runs
- Data was validated and inserted/updated
- File was moved to _OLD folder

### Step 5: Verify Import Results
```bash
php artisan tinker
```

In Tinker:
```php
// Check updated count
App\Models\Site::count()  // Should have increased

// View imported sites
App\Models\Site::whereNotNull('sap_site_code')->get(['name', 'sap_site_code', 'cut_off_day', 'billing_cycle'])

// Check import log
App\Models\SapImportLog::latest()->first()->toArray()
exit
```

### Step 6: Verify File Archiving
```bash
ls -lh "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD/"
# Should be empty (file moved)

ls -lh "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD_OLD/" | grep METER_CUTOFF
# Should show archived file with _processed_ timestamp
```

---

## Demo Part 2: User Import (4 minutes)

### Step 1: Show User File Structure
```bash
head -10 "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD_OLD/METER_USER_20251125_175251.CSV"
```

**Explain**: "This file contains SAP user IDs, names, emails, and roles"

### Step 2: Check Current Users
```bash
php artisan tinker
```

In Tinker:
```php
App\Models\User::count()
App\Models\User::whereNotNull('sap_user_id')->count()  // Note this
exit
```

### Step 3: Copy and Import
```bash
# Copy test file
cp "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD_OLD/METER_USER_20251125_175251.CSV" \
   "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD/"

# Run import
php artisan sap:import-users --verbose
```

**Expected Output**:
```
Starting SAP user import...
Found file: METER_USER_20251125_175251.CSV
Processing 4 rows...
✓ Import completed successfully
  Inserted: 3
  Updated: 0
  Skipped: 1 (duplicate SAP ID)
  Errors: 0
```

### Step 4: Verify Users Created
```bash
php artisan tinker
```

In Tinker:
```php
// Check new users
App\Models\User::whereNotNull('sap_user_id')->get(['name', 'email', 'sap_user_id', 'sap_role'])

// Check role mapping worked
App\Models\User::whereNotNull('sap_role')->pluck('sap_role')->unique()
exit
```

---

## Demo Part 3: Meter Import (5 minutes)

### Step 1: Show Meter File Complexity
```bash
head -5 "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD_OLD/METER_MASTER_20251114_075437.CSV"
```

**Explain**: "This file has 27 columns including meter numbers, measuring points, sites, and technical details"

### Step 2: Check Current Meters
```bash
php artisan tinker
```

In Tinker:
```php
App\Models\Meter::count()
App\Models\Meter::whereNotNull('sap_meter_number')->count()
App\Models\Meter::whereNotNull('gateway_id')->count()  // Note this
exit
```

### Step 3: Copy and Import
```bash
# Copy test file
cp "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD_OLD/METER_MASTER_20251114_075437.CSV" \
   "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD/"

# Run import
php artisan sap:import-meters --verbose
```

**Expected Output**:
```
Starting SAP meter import...
Found file: METER_MASTER_20251114_075437.CSV
Processing 692 rows...
⚠ Import completed with warnings
  Inserted: 0
  Updated: 0
  Skipped: 692 (no gateway assigned)
  Errors: 0
```

**Explain**: 
- Meters are skipped if they don't have a gateway assignment
- This matches the legacy PHP logic
- Data is still validated and logged
- Once gateways are assigned, re-running will insert/update them

### Step 4: Show Validation Logic
```bash
php artisan tinker
```

In Tinker:
```php
// Show why meters were skipped
$log = App\Models\SapImportLog::latest()->first();
echo $log->records_skipped . " meters skipped\n";
echo "Reason: Meters require gateway assignment before import\n";
exit
```

---

## Demo Part 4: Automated Scheduling (3 minutes)

### Step 1: Show Scheduled Tasks
```bash
php artisan schedule:list
```

**Expected Output**:
```
0 */5 * * * *  php artisan sap:import-sites ............... Next Due: X minutes from now
1 */5 * * * *  php artisan sap:import-users ............... Next Due: X minutes from now  
2 */5 * * * *  php artisan sap:import-meters .............. Next Due: X minutes from now
0 15 0 * * *   php artisan sap:export-readings ............ Next Due: Today at 00:15
```

**Explain**: 
- Imports run every 5 minutes (staggered by 1 minute each)
- Exports run daily at 00:15
- `withoutOverlapping()` prevents concurrent runs
- Lock files provide additional safety

### Step 2: Show Configuration
```bash
cat config/sap.php | grep -A 10 "'imports'"
```

**Explain**: "All import/export rules, column mappings, and validation are centralized here"

---

## Demo Part 5: Export Functionality (2 minutes)

### Step 1: Show Export Configuration
```bash
php artisan tinker
```

In Tinker:
```php
// Show export validation rules
$config = config('sap.exports.meter_readings');
print_r($config['validation_rules']);
exit
```

**Explain**: 
- Exports have 9 validation rules
- Only active meters with recent readings are exported
- Files are generated in SAP's expected format (6 columns)

### Step 2: Demonstrate Export (Dry Run)
```bash
php artisan sap:export-readings --cutoff-day=17 --verbose
```

**Expected Output** (if no valid data):
```
Starting SAP meter reading export...
Cut-off day: 17
Validating meters for export...
✓ Export completed
  Validated: 0 readings
  Exported: 0 readings
  Skipped: X (validation failed)
```

**Explain**: "Once meters have readings and meet validation criteria, files will be generated in UPLOAD directory"

---

## Demo Part 6: Monitoring & Logs (3 minutes)

### Step 1: View Database Logs
```bash
php artisan tinker
```

In Tinker:
```php
// Show all import history
App\Models\SapImportLog::latest()->take(5)->get(['import_type', 'file_name', 'records_inserted', 'records_updated', 'records_skipped', 'created_at'])->toArray()

// Show detailed log
$log = App\Models\SapImportLog::latest()->first();
echo "File: " . $log->file_name . "\n";
echo "Type: " . $log->import_type . "\n";
echo "Duration: " . $log->processing_time_seconds . "s\n";
echo "Status: " . $log->status . "\n";
exit
```

### Step 2: View Application Logs
```bash
tail -30 storage/logs/laravel.log | grep -i "sap"
```

**Explain**: "Every import/export is logged to both database and Laravel log for auditing"

### Step 3: Check Lock Directory
```bash
ls -la storage/app/sap/locks/
```

**Explain**: "Empty = no imports running. Lock files appear during processing to prevent conflicts"

---

## Comparison with Legacy System (2 minutes)

### Show Legacy Scripts
```bash
ls -lh "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/"{checker,import}_*.php
```

**Explain Improvements**:

| Feature | Legacy PHP | New Laravel System |
|---------|-----------|-------------------|
| Framework | Raw PHP | Laravel 12 with full ORM |
| Validation | Basic checks | Comprehensive validation rules |
| Error Handling | Limited | Try-catch with detailed logging |
| Logging | File-based | Database + Laravel log |
| Transactions | Manual | Automatic with rollback |
| Testing | None | Automated tests available |
| Configuration | Hardcoded | Centralized config file |
| Scheduling | Manual cron | Laravel scheduler |
| Documentation | Limited | Comprehensive docs |
| Maintainability | Difficult | Modern, maintainable code |

---

## Troubleshooting Demo (Optional, 2 minutes)

### Simulate Error Scenario
```bash
# Temporarily corrupt a CSV file
echo "INVALID,DATA,FORMAT" > "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD/TEST_CORRUPT.CSV"

# Try to import
php artisan sap:import-sites --verbose
```

**Expected Output**:
```
Starting SAP site import...
Found file: TEST_CORRUPT.CSV
Processing...
✗ Import failed: Invalid CSV format
Error logged to database
```

**Verify Error Logging**:
```bash
php artisan tinker
```

In Tinker:
```php
App\Models\SapImportLog::latest()->first()->toArray()  // status: 'failed'
exit
```

**Cleanup**:
```bash
rm "/Users/rli/Documents/DEC/SAP(SM) to CAMR/SAP_Files/AMR/DOWNLOAD/TEST_CORRUPT.CSV"
```

---

## Bonus: Legacy Endpoint Compatibility (2 minutes)

### Show Legacy Endpoint Aliases
```bash
php artisan route:list --name=legacy
```

**Expected Output**:
```
POST   http_post_server.php                              legacy.meter-readings.ingest
GET    rtu/index.php/rtu/rtu_check_update/{mac}/...     legacy.gateway.*
GET    check_time.php                                    legacy.server-time
```

**Explain**: 
- Legacy PHP endpoints maintained for backward compatibility
- Old gateways with hardcoded URLs continue to work
- All route to modern Laravel controllers (same code, different URLs)
- Zero-downtime migration from old PHP system

### Examples
```bash
# Modern endpoint
POST http://camr.test/api/meter-readings/ingest

# Legacy endpoint (same behavior)
POST http://camr.test/http_post_server.php
```

**Show in code**:
```bash
cat routes/web.php | grep -A 2 "http_post_server"
```

**Expected Output**:
```php
Route::post('/http_post_server.php', [MeterReadingController::class, 'ingest'])
    ->name('legacy.meter-readings.ingest');
```

---

## Summary & Next Steps (1 minute)

**What We've Demonstrated**:
✅ Automatic file detection and processing  
✅ Data validation and transformation  
✅ Insert/update logic (upsert)  
✅ File archiving with timestamps  
✅ Comprehensive error logging  
✅ Scheduled automation  
✅ Export capability  
✅ Superior to legacy system  

**Production Deployment Steps**:
1. ✅ Code committed and tested
2. ⏳ Enable cron: `* * * * * cd /path && php artisan schedule:run`
3. ⏳ Point SAP to drop files in DOWNLOAD directory
4. ⏳ Monitor first few runs
5. ⏳ Set up alerts for failed imports (optional)

**Questions to Ask Developer**:
1. Are there any edge cases in the legacy system we should handle?
2. What SAP export schedules are currently in production?
3. Who monitors the legacy imports currently?
4. Are there any undocumented validation rules?
5. What's the plan for gateway assignments to meters?

---

## Rollback Plan (Just in Case)

If issues arise:
```bash
# Restore legacy system
# (Legacy files still exist at original location)

# Rollback database migrations
php artisan migrate:rollback --step=4

# Disable scheduled tasks
# (Remove cron entry)
```

---

**Demo Complete!** 🎉
