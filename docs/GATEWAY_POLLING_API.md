# Gateway Polling API Documentation

## Overview

This document describes the Gateway Polling API used by deployed IoT gateway devices to synchronize configuration with the CAMR server. Gateways poll these endpoints every 10 minutes to check for updates.

**Implementation Date**: November 2025  
**Status**: Production

---

## Architecture

### Polling Workflows

Gateways implement three independent polling workflows:

1. **CSV Meter List Updates** - Download updated meter assignments
2. **Site Code Updates** - Download site location updates  
3. **Force Load Profile** - Server triggers immediate load profile upload

Additionally, gateways can synchronize their system time with the server.

### Polling Frequency

- **Standard**: Every 10 minutes per gateway
- **Expected Load**: ~144 requests per day per gateway per workflow
- **Total**: ~432 requests per day per gateway (all workflows combined)

---

## Endpoints

### Base URL

**Modern API** (recommended):
```
https://your-server.com/api
```

**Legacy URLs** (backward compatibility):
```
https://your-server.com
```

Both URL structures are supported and route to the same controller methods.

### Authentication

**None** - These endpoints have no authentication to maintain backward compatibility with deployed IoT devices that cannot be easily reconfigured.

⚠️ **Security Note**: Rate limiting is recommended (10-minute polling intervals).

### URL Compatibility

The API supports both modern RESTful URLs and legacy PHP URLs for backward compatibility:

| Workflow | Modern URL | Legacy PHP URL |
|----------|------------|----------------|
| Check CSV | `/api/gateway/{mac}/check/csv` | `/rtu/index.php/rtu/rtu_check_update/{mac}/get_update_csv` |
| Get CSV | `/api/gateway/{mac}/csv` | `/rtu/index.php/rtu/rtu_check_update/{mac}/get_content_csv` |
| Reset CSV | `/api/gateway/{mac}/csv/reset` | `/rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_csv` |
| Check Site | `/api/gateway/{mac}/check/site-code` | `/rtu/index.php/rtu/rtu_check_update/{mac}/get_update_location` |
| Get Site | `/api/gateway/{mac}/site-code` | `/rtu/index.php/rtu/rtu_check_update/{mac}/get_content_location` |
| Reset Site | `/api/gateway/{mac}/site-code/reset` | `/rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_location` |
| Check Load | `/api/gateway/{mac}/check/load-profile` | `/rtu/index.php/rtu/rtu_check_update/{mac}/force_lp` |
| Reset Load | `/api/gateway/{mac}/load-profile/reset` | `/rtu/index.php/rtu/rtu_check_update/{mac}/reset_force_lp` |
| Server Time | `/api/server-time` | `/check_time.php` |

All legacy URLs are aliases and return identical responses to the modern API.

---

## 1. CSV Meter List Updates

### 1.1 Check if Update Required

**Endpoint**: `GET /gateway/{mac}/check/csv`

**Description**: Gateway checks if a new meter list is available.

**URL Parameters**:
- `mac` (string, required) - Gateway MAC address (e.g., `AA:BB:CC:DD:EE:FF`)

**Response**:
- Content-Type: `text/plain`
- Body: `1` (update required) or `0` (no update)

**Example Request**:
```bash
curl https://your-server.com/api/gateway/AA:BB:CC:DD:EE:FF/check/csv
```

**Example Response**:
```
1
```

---

### 1.2 Download Meter List CSV

**Endpoint**: `GET /gateway/{mac}/csv`

**Description**: Download CSV file containing meter assignments for this gateway.

**URL Parameters**:
- `mac` (string, required) - Gateway MAC address

**Response**:
- Content-Type: `text/csv`
- Body: CSV data in format `meter_name,config_file,addressable_meter`

**CSV Format**:
```csv
meter_name,config_file,addressable_meter
12345678,decorp_zmd402.cfg,12345678
87654321,decorp_zmd402.cfg,11111111
```

**Rules**:
- Maximum 32 meters per gateway
- Only includes meters with `status = 'Active'`
- Includes configuration file name from `configuration_files` table
- Empty CSV if no meters assigned

**Example Request**:
```bash
curl https://your-server.com/api/gateway/AA:BB:CC:DD:EE:FF/csv
```

**Example Response**:
```csv
15003658,decorp_zmd402.cfg,15003658
15003659,decorp_zmd402.cfg,15003659
15003660,robinsons_sm.cfg,1
```

---

### 1.3 Reset Update Flag

**Endpoint**: `POST /gateway/{mac}/csv/reset`

**Description**: Gateway signals completion of CSV download. Server resets the `update_csv` flag to `false`.

**URL Parameters**:
- `mac` (string, required) - Gateway MAC address

**Response**:
- Content-Type: `text/plain`
- Body: `OK`

**Example Request**:
```bash
curl -X POST https://your-server.com/api/gateway/AA:BB:CC:DD:EE:FF/csv/reset
```

**Example Response**:
```
OK
```

---

## 2. Site Code Updates

### 2.1 Check if Update Required

**Endpoint**: `GET /gateway/{mac}/check/site-code`

**Description**: Gateway checks if site code has changed.

**URL Parameters**:
- `mac` (string, required) - Gateway MAC address

**Response**:
- Content-Type: `text/plain`
- Body: `1` (update required) or `0` (no update)

**Example Request**:
```bash
curl https://your-server.com/api/gateway/AA:BB:CC:DD:EE:FF/check/site-code
```

**Example Response**:
```
1
```

---

### 2.2 Download Site Code

**Endpoint**: `GET /gateway/{mac}/site-code`

**Description**: Download site code (location identifier) for this gateway.

**URL Parameters**:
- `mac` (string, required) - Gateway MAC address

**Response**:
- Content-Type: `text/plain`
- Body: `location = "{site_code}"`

**Example Request**:
```bash
curl https://your-server.com/api/gateway/AA:BB:CC:DD:EE:FF/site-code
```

**Example Response**:
```
location = "DECORP"
```

**Edge Cases**:
- Gateway not found → `location = ""`
- Site code not set → `location = ""`

---

### 2.3 Reset Update Flag

**Endpoint**: `POST /gateway/{mac}/site-code/reset`

**Description**: Gateway signals completion of site code update. Server resets the `update_site_code` flag to `false`.

**URL Parameters**:
- `mac` (string, required) - Gateway MAC address

**Response**:
- Content-Type: `text/plain`
- Body: `OK`

**Example Request**:
```bash
curl -X POST https://your-server.com/api/gateway/AA:BB:CC:DD:EE:FF/site-code/reset
```

**Example Response**:
```
OK
```

---

## 3. Force Load Profile

### 3.1 Check if Force Load Profile Required

**Endpoint**: `GET /gateway/{mac}/check/load-profile`

**Description**: Gateway checks if server wants immediate load profile upload.

**URL Parameters**:
- `mac` (string, required) - Gateway MAC address

**Response**:
- Content-Type: `text/plain`
- Body: `1` (upload now) or `0` (no action)

**Example Request**:
```bash
curl https://your-server.com/api/gateway/AA:BB:CC:DD:EE:FF/check/load-profile
```

**Example Response**:
```
1
```

**Note**: When flag is `1`, gateway immediately reads load profile data from meters and uploads to server. Load profile upload endpoint is separate (not yet implemented in this version).

---

### 3.2 Reset Force Load Profile Flag

**Endpoint**: `POST /gateway/{mac}/load-profile/reset`

**Description**: Gateway signals completion of load profile upload. Server resets the `force_load_profile` flag to `false`.

**URL Parameters**:
- `mac` (string, required) - Gateway MAC address

**Response**:
- Content-Type: `text/plain`
- Body: `OK`

**Example Request**:
```bash
curl -X POST https://your-server.com/api/gateway/AA:BB:CC:DD:EE:FF/load-profile/reset
```

**Example Response**:
```
OK
```

---

## 4. Server Time Synchronization

### 4.1 Get Server Time

**Endpoint**: `GET /server-time`

**Description**: Returns current server time for gateway clock synchronization.

**Response**:
- Content-Type: `text/plain`
- Body: `YYYY-MM-DD HH:MM:SS`

**Example Request**:
```bash
curl https://your-server.com/api/server-time
```

**Example Response**:
```
2025-11-27 08:30:15
```

**Usage**: Gateway compares local time with server time and adjusts its clock if different.

---

## Error Handling

### Philosophy

All endpoints follow a "fail-safe" approach:
- **Always return HTTP 200 OK** (never fail the gateway)
- Gateway not found → return safe default (`0`, empty string, or `OK`)
- Database errors → log and return safe default
- Invalid input → log and return safe default

### Example Error Scenarios

| Scenario | Endpoint | Response |
|----------|----------|----------|
| Unknown MAC address | Any check endpoint | `0` |
| Unknown MAC address | CSV content | Empty string |
| Unknown MAC address | Site code | `location = ""` |
| Unknown MAC address | Reset endpoints | `OK` |
| Database error | Any endpoint | Safe default + log error |
| No meters assigned | CSV content | Empty string |

---

## Typical Gateway Workflow

### Complete Polling Cycle

```
┌─────────────────────────────────────────────────────┐
│ Gateway Device (polls every 10 minutes)            │
└────────────┬────────────────────────────────────────┘
             │
             ├─→ GET /gateway/{mac}/check/csv
             │   Response: 1
             │
             ├─→ GET /gateway/{mac}/csv
             │   Response: CSV data (32 meters)
             │
             ├─→ POST /gateway/{mac}/csv/reset
             │   Response: OK
             │
             ├─→ GET /gateway/{mac}/check/site-code
             │   Response: 0
             │
             ├─→ GET /gateway/{mac}/check/load-profile
             │   Response: 0
             │
             └─→ GET /server-time
                 Response: 2025-11-27 08:30:15
```

### Administrator Workflow

To trigger a meter list update for a gateway:

1. **Admin UI**: Set `update_csv = true` for gateway
2. **Gateway**: Next poll cycle detects flag = 1
3. **Gateway**: Downloads new CSV
4. **Gateway**: Resets flag via API
5. **Server**: Sets `update_csv = false`

Same pattern applies for site code and force load profile updates.

---

## Database Schema

### Gateway Flags

The `gateways` table includes three boolean flags:

```sql
CREATE TABLE gateways (
    ...
    update_csv BOOLEAN DEFAULT 0,           -- Trigger CSV download
    update_site_code BOOLEAN DEFAULT 0,     -- Trigger site code download  
    force_load_profile BOOLEAN DEFAULT 0,   -- Trigger load profile upload
    site_code VARCHAR(255),                 -- Location identifier
    mac_address VARCHAR(255) UNIQUE,        -- Gateway identifier
    ...
);
```

### CSV Generation Query

The CSV endpoint generates meter lists using:

```sql
SELECT meters.name, meters.default_name, configuration_files.meter_model
FROM meters
LEFT JOIN configuration_files ON meters.configuration_file_id = configuration_files.id
WHERE meters.gateway_id = :gateway_id
  AND meters.status = 'Active'
LIMIT 32
```

---

## Logging

All endpoints log their activity:

### Check Endpoints
```
Gateway CSV update check
Gateway site code update check  
Gateway force load profile check
```

### Content Endpoints
```
Gateway CSV content generated (mac, gateway_id, meter_count)
Gateway site code retrieved (mac, gateway_id, site_code)
```

### Reset Endpoints
```
Gateway CSV update flag reset (mac, gateway_id)
Gateway site code update flag reset (mac, gateway_id)
Gateway force load profile flag reset (mac, gateway_id)
```

### Errors
```
Failed to check CSV update (mac, error)
Failed to generate CSV content (mac, error)
CSV content requested for unknown gateway (mac)
```

---

## Testing

### Feature Tests

Location: `tests/Feature/GatewayPollingTest.php`

**Test Coverage**:
- ✅ Flag checks (0/1 responses)
- ✅ CSV generation (format, filtering, limits)
- ✅ Site code retrieval
- ✅ Flag resets
- ✅ Unknown gateway handling
- ✅ Empty data scenarios
- ✅ Server time format

**Run Tests**:
```bash
php artisan test --filter=GatewayPollingTest
```

### Manual Testing

```bash
# Check CSV update
curl http://localhost:8000/api/gateway/AA:BB:CC:DD:EE:FF/check/csv

# Get CSV content
curl http://localhost:8000/api/gateway/AA:BB:CC:DD:EE:FF/csv

# Reset flag
curl -X POST http://localhost:8000/api/gateway/AA:BB:CC:DD:EE:FF/csv/reset

# Get server time
curl http://localhost:8000/api/server-time
```

---

## Performance Considerations

### Expected Load

- 10-minute polling = 6 requests/hour per gateway
- 3 workflows × 6 = 18 requests/hour per gateway  
- 100 gateways = 1,800 requests/hour
- 1,000 gateways = 18,000 requests/hour

### Optimization

- ✅ Simple database queries (indexed by MAC address)
- ✅ Minimal data transfer (plain text responses)
- ✅ No authentication overhead
- ⚠️ Consider caching gateway lookups if >1000 gateways
- ⚠️ Consider rate limiting per MAC address

---

## Future Enhancements

### Load Profile Upload Endpoint

When `force_load_profile` flag is set, gateway will upload files to:
```
storage/app/load_profiles/{site_code}/
```

This endpoint is not yet implemented.

### Legacy URL Aliases

If gateways cannot be reconfigured, add compatibility routes:
```
GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_update_csv
GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_content_csv
POST /rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_csv
...
GET /check_time.php
```

### Rate Limiting

Add middleware to limit requests:
```php
Route::middleware(['throttle:gateway'])->group(...);
```

### Audit Trail

Track flag changes:
- Who enabled `update_csv`?
- When was it enabled?
- When did gateway download?

---

## Related Documentation

- **Meter Reading Ingestion**: `docs/METER_DATA_API.md`
- **Implementation Plan**: `docs/GATEWAY_POLLING_IMPLEMENTATION_PLAN.md`
- **Gateway to CAMR Server PDF**: `~/Downloads/Gateway to CAMR Server.pdf`

---

## Support

For questions or issues with gateway polling:
1. Check logs: `storage/logs/laravel.log`
2. Run tests: `php artisan test --filter=GatewayPollingTest`
3. Verify database flags in `gateways` table
4. Check gateway MAC address matches database exactly
