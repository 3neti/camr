# Live Data Migration Tools

This directory contains tools to extract and import real data from the legacy SM AMR SQL dump.

## Files

### `SqlDumpParser.php`
A robust SQL dump parser that can extract data from the legacy `meter_reading.sql` backup file.

**Features:**
- Parses CREATE TABLE and INSERT statements
- Handles binary data, escaped strings, NULL values
- Exports data to JSON for inspection
- Provides statistics about the dump

**Usage:**
```php
$parser = new SqlDumpParser('/path/to/dump.sql');
$parser->parse();

// Get all table names
$tables = $parser->getTableNames();

// Get rows as associative arrays
$rows = $parser->getTableRows('meter_data');

// Export to JSON for inspection
$parser->exportTableToJson('meter_data', '/path/to/output.json');

// Get statistics
$stats = $parser->getStatistics();
```

### `LiveDataSeeder.php`
Seeder that imports real meter readings from the SQL dump into the new Laravel database.

**Features:**
- Extracts last 7 days of meter readings from dump
- Maps old database structure to new structure
- Handles date/time conversions and validation
- Provides progress feedback

**Usage:**
```bash
# Run the live data seeder
php artisan db:seed --class=LiveDataSeeder

# Export statistics about the dump
php artisan tinker
>>> (new Database\Seeders\LiveDataSeeder)->exportStatistics()

# Export sample data for inspection
>>> (new Database\Seeders\LiveDataSeeder)->exportSamples()
```

## Original Seeders Backup

All original seeders have been backed up to: `backup_original/`

These seeders generate synthetic/test data for visualization:
- `MeterDataSeeder.php` - Generates fake meter readings
- `SiteSeeder.php` - Creates test sites
- `GatewaySeeder.php` - Creates test gateways
- etc.

## Data Mapping

### Old Structure → New Structure

The `LiveDataSeeder` maps fields from the legacy database to the new structure:

| Legacy Field | New Field | Notes |
|--------------|-----------|-------|
| `location` | `location` | Direct mapping |
| `meter_id` | `meter_name` | Renamed |
| `datetime` | `reading_datetime` | Renamed |
| `vrms_a/b/c` | `vrms_a/b/c` | Direct mapping |
| `irms_a/b/c` | `irms_a/b/c` | Direct mapping |
| `freq` | `frequency` | Renamed |
| `pf` | `power_factor` | Renamed |
| `wh_del` | `wh_delivered` | Renamed |
| `wh_rec` | `wh_received` | Renamed |
| `varh_neg` | `varh_negative` | Renamed |
| `varh_pos` | `varh_positive` | Renamed |
| `mac_addr` | `mac_address` | Renamed |
| `soft_rev` | `software_version` | Renamed |

## SQL Dump Location

The parser expects the SQL dump at:
```
/Users/rli/Documents/DEC/backup/meter_reading/meter_reading.sql
```

Update the path in `LiveDataSeeder.php` if your dump is located elsewhere.

## Full Migration Plan

For a complete migration from the old system:

1. **Phase 1: Data Analysis** ✅
   - [x] Parse SQL dump
   - [x] Export statistics
   - [x] Export sample data

2. **Phase 2: Meter Data** (Current)
   - [x] Import recent meter_data (last 7 days)
   - [ ] Verify data accuracy
   - [ ] Test with UI

3. **Phase 3: Configuration Data**
   - [ ] Import meter_details (meters)
   - [ ] Import meter_rtu (gateways)
   - [ ] Import meter_site (sites)
   - [ ] Import user accounts

4. **Phase 4: Historical Data**
   - [ ] Import full meter_data history
   - [ ] Batch processing for large datasets
   - [ ] Data validation and cleanup

5. **Phase 5: Cutover**
   - [ ] Final sync
   - [ ] Production testing
   - [ ] Go live

## Tips

### Performance Optimization

For large datasets:
```php
// Use chunking
DB::table('meter_data')->insert(array_chunk($data, 1000));

// Disable timestamps temporarily
Model::unguard();
Model::withoutTimestamps(function() {
    // bulk insert
});
```

### Debugging

Export sample data to inspect format:
```bash
php artisan tinker
>>> $seeder = new Database\Seeders\LiveDataSeeder;
>>> $seeder->exportSamples();
```

Check files in `storage/app/`:
- `dump_statistics.json` - Table statistics
- `sample_meter_data.json` - Sample readings
- `sample_meter_details.json` - Sample meter config

## Notes

- The parser handles ~26K lines of SQL
- Supports binary data (skipped, not needed)
- Handles escaped quotes and special characters
- Invalid dates (`0000-00-00 00:00:00`) are filtered out
- Only recent data (7 days) is imported by default to test the system

## Next Steps

1. Run the live data seeder
2. Check the imported data in the UI
3. If successful, extend to import more tables
4. Plan full historical data migration
