# Live Data Migration Tools - Summary

## What Was Created

### 1. SQL Dump Parser (`database/seeders/SqlDumpParser.php`)
A robust parser that extracts data from your legacy SQL dump file.

**Key Features:**
- ✅ Parses 25,772 lines of SQL dump
- ✅ Handles binary data, escaped strings, NULL values
- ✅ Extracts ~30 tables with INSERT data
- ✅ Exports to JSON for inspection
- ✅ Provides statistics

### 2. Live Data Seeder (`database/seeders/LiveDataSeeder.php`)
Imports real meter readings into your new Laravel database.

**Key Features:**
- ✅ Maps old structure → new structure
- ✅ Imports last 7 days of readings
- ✅ Validates dates and data types
- ✅ Progress feedback
- ✅ Transaction safety

### 3. Inspection Command (`app/Console/Commands/InspectSqlDump.php`)
CLI tool to analyze the SQL dump before importing.

**Key Features:**
- ✅ View table statistics
- ✅ Preview sample data
- ✅ Export tables to JSON
- ✅ List all tables

### 4. Backups
All original test seeders backed up to: `database/seeders/backup_original/`

## Quick Start

### Step 1: Inspect the Dump
```bash
# Get overview
php artisan dump:inspect

# See all tables
php artisan dump:inspect --tables

# View detailed statistics
php artisan dump:inspect --stats

# Preview meter_data
php artisan dump:inspect --sample=meter_data

# Export to JSON for analysis
php artisan dump:inspect --export=meter_data
```

### Step 2: Test with Sample Meters
First, make sure you have some meters in your database:
```bash
# Run the original seeders to create structure
php artisan migrate:fresh
php artisan db:seed
```

### Step 3: Import Live Data
```bash
# Import real meter readings (last 7 days)
php artisan db:seed --class=LiveDataSeeder
```

### Step 4: Verify in UI
Check your dashboard to see live data visualized!

## What the Tools Do

### Data Flow

```
Legacy SQL Dump (meter_reading.sql)
          ↓
    SqlDumpParser
          ↓
   Extract & Parse
          ↓
   LiveDataSeeder
          ↓
 Map old → new structure
          ↓
  Laravel Database
          ↓
      Your UI
```

### Field Mapping

The seeder automatically maps:

| Old Database | → | New Database |
|--------------|---|--------------|
| `meter_id` | → | `meter_name` |
| `datetime` | → | `reading_datetime` |
| `freq` | → | `frequency` |
| `pf` | → | `power_factor` |
| `wh_del` | → | `wh_delivered` |
| `wh_rec` | → | `wh_received` |
| `mac_addr` | → | `mac_address` |
| `soft_rev` | → | `software_version` |

## Files Created

```
PhpstormProjects/camr/
├── database/seeders/
│   ├── SqlDumpParser.php           # Parser class
│   ├── LiveDataSeeder.php          # Import seeder
│   ├── README_LIVE_DATA.md         # Detailed docs
│   └── backup_original/            # Original seeders
│       ├── MeterDataSeeder.php     # (backed up)
│       ├── SiteSeeder.php          # (backed up)
│       └── ...
├── app/Console/Commands/
│   └── InspectSqlDump.php         # Inspection tool
└── MIGRATION_TOOLS.md              # This file
```

## Data in the Dump

Your SQL dump contains:

**Tables Found:**
- `meter_data` - ~19,836 meter readings
- `meter_details` - Meter configuration
- `meter_rtu` - Gateway/RTU data
- `meter_site` - Site information
- `user_tb` - User accounts
- `load_profile` - Load profile data
- And ~25 more tables

**Date Range:** 
- Latest: 2025-10-10
- Importing: Last 7 days from latest

## Next Steps

### Testing (Now)
1. ✅ Inspect dump with CLI tool
2. ✅ Import recent data (7 days)
3. ⏳ Verify data accuracy in UI
4. ⏳ Compare with original system

### Full Migration (Later)
1. Import meter configuration
2. Import gateway/RTU data
3. Import site structure
4. Import user accounts
5. Import full historical data
6. Production cutover

## Performance Notes

**Current Import (7 days):**
- ~19,836 total readings in dump
- ~1,000-2,000 readings (last 7 days)
- Takes ~30-60 seconds

**Full Historical Import:**
- Could be millions of records
- Will need batch processing
- Recommend chunking in 10k batches
- Estimate: 2-6 hours for full import

## Troubleshooting

### Parser Issues
```bash
# Check if file exists
ls -lh /Users/rli/Documents/DEC/backup/meter_reading/meter_reading.sql

# Test parsing
php artisan dump:inspect --stats
```

### Import Issues
```bash
# Check database connection
php artisan tinker
>>> DB::connection()->getPdo()

# Export sample to debug
php artisan tinker
>>> (new Database\Seeders\LiveDataSeeder)->exportSamples()

# Check exported files
ls -lh storage/app/sample_*.json
```

### Data Validation
```bash
# Count imported records
php artisan tinker
>>> App\Models\MeterData::count()
>>> App\Models\MeterData::latest('reading_datetime')->first()
```

## Safety Features

✅ **Transaction wrapped** - Rolls back on error  
✅ **Date validation** - Skips invalid dates  
✅ **Type conversion** - Handles NULL, empty strings  
✅ **Progress tracking** - Shows import status  
✅ **Backups created** - Original seeders preserved  

## Support

For issues or questions:
1. Check `database/seeders/README_LIVE_DATA.md`
2. Inspect data with `php artisan dump:inspect`
3. Export samples for debugging
4. Review Laravel logs in `storage/logs/`

## Success Criteria

✅ Parser runs without errors  
✅ Statistics show expected tables/rows  
✅ Sample data looks correct  
✅ Import completes successfully  
✅ Data appears in UI  
✅ Dates are in correct range  
✅ Values match original system  

**When all criteria pass → Ready for full migration!**
