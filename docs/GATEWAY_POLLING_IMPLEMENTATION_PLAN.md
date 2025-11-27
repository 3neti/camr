# Gateway Polling API Implementation

## Problem Statement

Gateways deployed in the field poll the CAMR server every 10 minutes for configuration updates via legacy PHP endpoints. We need to implement modern Laravel API endpoints that maintain backward compatibility with the old URL structure while enabling three gateway workflows:

1. **CSV Meter List Updates** - Gateway polls for updated meter assignments (up to 32 active meters)
2. **Site Code Updates** - Gateway polls for location/site code changes
3. **Force Load Profile** - Gateway checks if server wants immediate load profile upload

Additionally, there's a time synchronization endpoint for gateway clock accuracy.

## Current State

### Database Schema

The CAMR system already has the necessary infrastructure:

- **gateways table** has flag columns:
  - `update_csv` (boolean) - triggers meter list download
  - `update_site_code` (boolean) - triggers site code download
  - `force_load_profile` (boolean) - triggers immediate load profile upload
  - `site_code` (string) - the location identifier
  - `mac_address` (string, unique) - gateway identifier

- **meters table** has:
  - `gateway_id` (foreign key)
  - `configuration_file_id` (foreign key)
  - `name` (string) - meter serial number
  - `default_name` (string) - addressable meter identifier
  - `role` (string) - 'Client Meter', 'CUSA', 'Check Meter'
  - `status` (string) - 'Active', 'Inactive'

- **configuration_files table** has:
  - `meter_model` (string) - the config filename (e.g., 'decorp_zmd402.cfg')

### Existing Endpoints

- `POST /api/meter-readings/ingest` - Already handles meter data ingestion (MeterReadingController)
- No polling endpoints exist yet

### Legacy URL Structure (from PDF)

Old PHP endpoints that gateways currently call:
- `/rtu/index.php/rtu/rtu_check_update/{mac}/get_update_csv`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/get_content_csv`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_csv`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/get_update_location`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/get_content_location`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_location`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/force_lp`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/reset_force_lp`
- `/check_time.php` (root level)

### Root Endpoint Issue

User mentioned that `api/meter-readings/ingest` is actually accessed at root `/`. This needs investigation but is deferred as a separate TODO item.

## Proposed Solution

### Endpoint Design

Create modern RESTful endpoints under `/api/gateway/{mac}` while maintaining legacy URL compatibility:

#### Option A: Modern URLs Only (Recommended)

```
GET  /api/gateway/{mac}/check/csv          → returns 1 or 0
GET  /api/gateway/{mac}/csv                → returns CSV content
POST /api/gateway/{mac}/csv/reset         → sets flag to 0

GET  /api/gateway/{mac}/check/site-code   → returns 1 or 0  
GET  /api/gateway/{mac}/site-code         → returns location="CODE"
POST /api/gateway/{mac}/site-code/reset   → sets flag to 0

GET  /api/gateway/{mac}/check/load-profile → returns 1 or 0
POST /api/gateway/{mac}/load-profile/reset → sets flag to 0

GET  /api/server-time                      → returns YYYY-MM-DD HH:MM:SS
```

#### Option B: Maintain Legacy URLs (For Backward Compatibility)

If deployed gateways cannot be reconfigured, add routes:

```
GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_update_csv
GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_content_csv
POST /rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_csv
... (all legacy paths)
GET /check_time.php
```

**Decision**: Start with Option A, add legacy URL aliases later if needed.

### Implementation Components

#### 1. GatewayPollingController

New controller at `app/Http/Controllers/Api/GatewayPollingController.php`:

- `checkCsvUpdate(string $mac)` - Check if gateway needs CSV update
- `getCsvContent(string $mac)` - Generate meter list CSV (first 32 active meters)
- `resetCsvUpdate(string $mac)` - Reset update_csv flag to false
- `checkSiteCodeUpdate(string $mac)` - Check if gateway needs site code update
- `getSiteCode(string $mac)` - Return site code
- `resetSiteCodeUpdate(string $mac)` - Reset update_site_code flag
- `checkForceLoadProfile(string $mac)` - Check if force load profile enabled
- `resetForceLoadProfile(string $mac)` - Reset force_load_profile flag
- `getServerTime()` - Return current server time

#### 2. CSV Generation Logic

From PDF documentation, CSV format is:
```
meter_name,config_file,addressable_meter
```

Rules:
- First 32 meters only (per gateway)
- Filter: `status = 'Active'`
- Filter: `role IN ('Client Meter', 'CUSA', 'Check Meter')` for SM/Robinsons (make configurable)
- Name logic (from legacy PHP):
  ```php
  if ($meter->name == $meter->default_name) {
      $meter_name = $meter->name;
      $addressable = $meter->default_name;
  } elseif ($meter->default_name == '1') {
      $meter_name = '1';
      $addressable = $meter->default_name;
  } else {
      $meter_name = $meter->name;
      $addressable = $meter->default_name;
  }
  ```

#### 3. Response Formats

- Flag checks: Return plain text `1` or `0`
- CSV content: Return `text/csv` with proper line endings (`\n`)
- Site code: Return plain text `location = "{site_code}"`
- Server time: Return plain text `YYYY-MM-DD HH:MM:SS`
- Reset operations: Return plain text `OK` or HTTP 200

#### 4. Load Profile Storage (Future)

When gateway sends load profile (triggered by force_load_profile=1), it will POST to existing ingestion endpoint. Files stored at:
```
storage/app/load_profiles/{site_code}/
```

Auto-create directory if not exists. This is outside current scope but documented for context.

### Error Handling

- Gateway not found by MAC → return `0` (don't break device)
- No meters assigned → return empty CSV
- Database errors → log and return safe default (0 or empty)
- Follow MeterReadingController pattern: always return 200 OK

### Security Considerations

- No authentication (matches existing meter ingestion endpoint)
- Rate limiting recommended (10-minute polling = ~144 requests/day per gateway)
- Input validation: MAC address format
- SQL injection protection: use Eloquent (already built-in)

### Testing Strategy

- Feature tests for each endpoint
- Test with real MAC addresses from database
- Test edge cases: no meters, inactive meters, missing config files
- Test CSV formatting (line endings, special characters)
- Performance test: CSV generation for gateway with 32+ meters

## Implementation Steps

1. Create GatewayPollingController with all 9 methods
2. Add routes to `routes/api.php` under `/api/gateway/{mac}` prefix
3. Implement CSV generation logic following legacy PHP algorithm
4. Create comprehensive feature tests
5. Test with real gateway data from development database
6. Document in `docs/GATEWAY_POLLING_API.md` (similar to METER_DATA_API.md)
7. Add TODO for root endpoint investigation (meter-readings/ingest vs /)
8. (Future) Add legacy URL aliases if needed for backward compatibility

## Success Criteria

- ✅ All 8 gateway polling endpoints functional
- ✅ CSV contains correct meter data (first 32 active meters)
- ✅ Flags reset correctly after gateway acknowledgment  
- ✅ Server time endpoint returns accurate timestamp
- ✅ Response formats match legacy expectations (plain text, not JSON)
- ✅ Gateway not found scenarios handled gracefully
- ✅ Tests cover all workflows and edge cases
- ✅ Documentation complete

## Future Enhancements

- Add legacy URL aliases if gateways cannot be reconfigured
- Implement load profile file upload handler
- Add rate limiting middleware
- Add gateway activity monitoring dashboard
- Add audit trail for flag changes (who enabled update_csv and when)

## References

- Source documentation: `~/Downloads/Gateway to CAMR Server.pdf`
- Existing meter ingestion: `app/Http/Controllers/Api/MeterReadingController.php`
- Existing API docs: `docs/METER_DATA_API.md`
