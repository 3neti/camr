# CAMR Endpoint Migration Guide

## Overview
CAMR supports both **legacy PHP endpoints** and **modern Laravel API endpoints**. Both sets of endpoints route to the same controllers, ensuring identical behavior while maintaining backward compatibility with deployed IoT devices.

## Endpoint Mapping

### 🔄 Meter Reading Ingestion

| Legacy Endpoint | Modern API Endpoint | Controller |
|----------------|---------------------|------------|
| `POST /http_post_server.php` | `POST /api/meter-readings/ingest` | `MeterReadingController@ingest` |

**Use Case**: Gateways/meters send meter data (voltage, current, power, energy readings)

**Documentation**: 
- `docs/METER_DATA_API.md` - Complete protocol
- `docs/LEGACY_ENDPOINT_TEST.md` - Testing guide

---

### 📡 Gateway Polling - CSV Meter List Updates

| Legacy Endpoint | Modern API Endpoint | Controller |
|----------------|---------------------|------------|
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_update_csv` | `GET /api/gateway/{mac}/check/csv` | `GatewayPollingController@checkCsvUpdate` |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_content_csv` | `GET /api/gateway/{mac}/csv` | `GatewayPollingController@getCsvContent` |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_csv` | `POST /api/gateway/{mac}/csv/reset` | `GatewayPollingController@resetCsvUpdate` |

**Use Case**: Gateway polls for updated meter assignments (up to 32 active meters)

---

### 📍 Gateway Polling - Site Code Updates

| Legacy Endpoint | Modern API Endpoint | Controller |
|----------------|---------------------|------------|
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_update_location` | `GET /api/gateway/{mac}/check/site-code` | `GatewayPollingController@checkSiteCodeUpdate` |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_content_location` | `GET /api/gateway/{mac}/site-code` | `GatewayPollingController@getSiteCode` |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_location` | `POST /api/gateway/{mac}/site-code/reset` | `GatewayPollingController@resetSiteCodeUpdate` |

**Use Case**: Gateway polls for location/site code changes

---

### 📊 Gateway Polling - Force Load Profile

| Legacy Endpoint | Modern API Endpoint | Controller |
|----------------|---------------------|------------|
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/force_lp` | `GET /api/gateway/{mac}/check/load-profile` | `GatewayPollingController@checkForceLoadProfile` |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/reset_force_lp` | `POST /api/gateway/{mac}/load-profile/reset` | `GatewayPollingController@resetForceLoadProfile` |

**Use Case**: Server triggers immediate load profile upload from gateway

---

### ⏰ Server Time Synchronization

| Legacy Endpoint | Modern API Endpoint | Controller |
|----------------|---------------------|------------|
| `GET /check_time.php` | `GET /api/server-time` | `GatewayPollingController@getServerTime` |

**Use Case**: Gateway synchronizes its clock with server time

---

## Migration Strategy

### Phase 1: Dual Support (Current)
✅ **Both endpoints active** - Legacy and modern URLs work simultaneously  
✅ **Zero downtime** - Deployed devices continue working  
✅ **Single codebase** - One controller method handles both URLs

### Phase 2: Gradual Rollout
- New gateway deployments use modern API endpoints
- Old gateways continue using legacy endpoints
- Monitor traffic split between legacy/modern

### Phase 3: Deprecation (Future)
- Set deprecation date (e.g., 12 months from now)
- Send firmware updates to reconfigure legacy gateways
- Monitor legacy endpoint usage decline

### Phase 4: Removal (Optional)
- Remove legacy routes when usage reaches zero
- Maintain modern API endpoints only

---

## Configuration Examples

### Gateway Configuration - Legacy Format
```ini
[SERVER]
# Old PHP system URLs
METER_POST_URL=http://camr.test/http_post_server.php
CHECK_CSV_URL=http://camr.test/rtu/index.php/rtu/rtu_check_update/{{MAC}}/get_update_csv
CHECK_TIME_URL=http://camr.test/check_time.php
```

### Gateway Configuration - Modern Format
```ini
[SERVER]
# New Laravel API URLs
METER_POST_URL=http://camr.test/api/meter-readings/ingest
CHECK_CSV_URL=http://camr.test/api/gateway/{{MAC}}/check/csv
CHECK_TIME_URL=http://camr.test/api/server-time
```

---

## Testing Both Endpoints

### Quick Route Check
```bash
# Show all legacy endpoints
php artisan route:list --name=legacy

# Show all modern API endpoints
php artisan route:list --name=api
```

### Test Legacy Meter Ingestion
```bash
curl -X POST http://camr.test/http_post_server.php \
  -d "meter_id=TEST_001" \
  -d "datetime=2025-11-28%2010:00:00" \
  -d "watt=1200" \
  -d "wh_total=12345.67"
```

### Test Modern API Meter Ingestion
```bash
curl -X POST http://camr.test/api/meter-readings/ingest \
  -d "meter_id=TEST_001" \
  -d "datetime=2025-11-28%2010:00:00" \
  -d "watt=1200" \
  -d "wh_total=12345.67"
```

**Both should return**: `OK`

---

## Monitoring Endpoint Usage

### View Logs for Both Endpoints
```bash
# Watch all meter ingestion (both legacy and modern)
tail -f storage/logs/laravel.log | grep -E "(http_post_server|meter-readings/ingest)"

# Watch gateway polling (both legacy and modern)
tail -f storage/logs/laravel.log | grep -E "(rtu_check_update|api/gateway)"
```

### Database Query
```php
// Check recent meter data sources
DB::table('meter_data')
    ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
    ->groupBy('date')
    ->orderBy('date', 'desc')
    ->limit(7)
    ->get();
```

---

## Advantages of Dual Support

### ✅ **For Operations**
- **Zero downtime** during migration
- **Gradual rollout** reduces risk
- **Fallback option** if issues arise
- **No gateway reconfiguration** required immediately

### ✅ **For Development**
- **Single codebase** - no code duplication
- **Easier testing** - test both formats
- **Clear migration path** - documented strategy
- **Modern API** for new integrations

### ✅ **For IoT Devices**
- **Backward compatible** - old firmware works
- **No service interruption** during migration
- **Flexible upgrade schedule** - device by device
- **Same behavior** regardless of URL format

---

## Route Implementation

### Legacy Routes (web.php)
```php
// Meter reading ingestion
Route::post('/http_post_server.php', [MeterReadingController::class, 'ingest'])
    ->name('legacy.meter-readings.ingest');

// Gateway polling endpoints
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/get_update_csv', [GatewayPollingController::class, 'checkCsvUpdate'])
    ->name('legacy.gateway.check-csv');
    
Route::get('/check_time.php', [GatewayPollingController::class, 'getServerTime'])
    ->name('legacy.server-time');
```

### Modern API Routes (api.php)
```php
// Meter reading ingestion
Route::post('/meter-readings/ingest', [MeterReadingController::class, 'ingest'])
    ->name('api.meter-readings.ingest');

// Gateway polling endpoints
Route::prefix('gateway/{mac}')->group(function () {
    Route::get('/check/csv', [GatewayPollingController::class, 'checkCsvUpdate'])
        ->name('api.gateway.check-csv');
});

Route::get('/server-time', [GatewayPollingController::class, 'getServerTime'])
    ->name('api.server-time');
```

---

## Security Considerations

### Authentication
- ❌ **No authentication required** - maintains backward compatibility
- 🔒 **Network-level security** recommended (VPN, firewall rules)
- 📊 **Rate limiting** should be considered (10-min polling = 144 req/day per gateway)

### Validation
- ✅ All inputs validated in controller
- ✅ SQL injection protection via Eloquent
- ✅ Invalid data logged but doesn't break devices
- ✅ Always returns `200 OK` to prevent device errors

---

## Related Documentation

- **`docs/METER_DATA_API.md`** - Complete meter ingestion protocol
- **`docs/LEGACY_ENDPOINT_TEST.md`** - Testing procedures
- **`docs/GATEWAY_POLLING_API.md`** - Gateway polling workflows
- **`docs/SAP_INTEGRATION.md`** - SAP data sync system
- **`tests/Feature/MeterReadingIngestionTest.php`** - Automated tests

---

## Summary

| Aspect | Status |
|--------|--------|
| Legacy Endpoints | ✅ Active |
| Modern API Endpoints | ✅ Active |
| Code Duplication | ❌ None - same controllers |
| Backward Compatibility | ✅ 100% |
| Migration Strategy | ✅ Documented |
| Testing | ✅ Comprehensive |
| Documentation | ✅ Complete |

**Both legacy and modern endpoints are production-ready!** 🚀
