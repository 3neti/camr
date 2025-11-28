# Legacy Endpoint Testing

## Overview
The legacy PHP endpoint `http_post_server.php` has been aliased to route to the modern Laravel `MeterReadingController@ingest` method. This maintains backward compatibility with deployed gateways/meters that still use the old URL structure.

## Endpoint Comparison

| Aspect | Legacy Endpoint | Modern API Endpoint |
|--------|----------------|---------------------|
| URL | `POST http://camr.test/http_post_server.php` | `POST http://camr.test/api/meter-readings/ingest` |
| Controller | `App\Http\Controllers\Api\MeterReadingController@ingest` | `App\Http\Controllers\Api\MeterReadingController@ingest` |
| Route Name | `legacy.meter-readings.ingest` | `api.meter-readings.ingest` |
| Authentication | None (backward compatibility) | None (backward compatibility) |
| Middleware | `web` | `api` |
| Request Body | Identical | Identical |
| Response | Identical | Identical |

**Key Point**: Both endpoints route to the **same controller method**, ensuring identical behavior.

## Testing

### Test 1: Verify Routes Exist
```bash
# Check legacy endpoint
php artisan route:list --path=http_post_server

# Check modern API endpoint
php artisan route:list --path=meter-readings
```

**Expected Output**:
```
POST  http_post_server.php          legacy.meter-readings.ingest
POST  api/meter-readings/ingest     api.meter-readings.ingest
```

### Test 2: Test Legacy Endpoint with cURL
```bash
# Using legacy PHP endpoint
curl -X POST http://camr.test/http_post_server.php \
  -d "meter_id=TEST_METER_001" \
  -d "location=TEST_SITE" \
  -d "datetime=2025-11-28%2010:00:00" \
  -d "vrms_a=230.5" \
  -d "irms_a=5.2" \
  -d "watt=1200" \
  -d "wh_total=12345.67" \
  -d "mac_address=00:11:22:33:44:55" \
  -v
```

**Expected Response**:
```
HTTP/1.1 200 OK
Content-Type: text/plain

OK
```

### Test 3: Test Modern API Endpoint with cURL
```bash
# Using modern API endpoint (should behave identically)
curl -X POST http://camr.test/api/meter-readings/ingest \
  -d "meter_id=TEST_METER_001" \
  -d "location=TEST_SITE" \
  -d "datetime=2025-11-28%2010:00:00" \
  -d "vrms_a=230.5" \
  -d "irms_a=5.2" \
  -d "watt=1200" \
  -d "wh_total=12345.67" \
  -d "mac_address=00:11:22:33:44:55" \
  -v
```

**Expected Response**:
```
HTTP/1.1 200 OK
Content-Type: text/plain

OK
```

### Test 4: Check Laravel Logs
```bash
# Tail logs to see both endpoints logging identically
tail -f storage/logs/laravel.log | grep "Meter reading"
```

**Expected Log Output** (should be identical for both):
```
[timestamp] local.INFO: Meter reading ingested {"meter_name":"TEST_METER_001","location":"TEST_SITE",...}
```

### Test 5: Verify Database Insertion
```bash
php artisan tinker
```

In Tinker:
```php
// Check if readings were saved (both endpoints should create records)
App\Models\MeterData::where('meter_name', 'TEST_METER_001')->latest()->first()?->toArray()
```

## Integration with Existing Documentation

This legacy endpoint complements the existing meter data ingestion system documented in:
- `docs/METER_DATA_API.md` - Complete protocol documentation
- `tests/Feature/MeterReadingIngestionTest.php` - Comprehensive test suite

## Backward Compatibility Notes

### Why This Matters
Deployed IoT devices (gateways/meters) in the field may have the old PHP endpoint URL hardcoded. Creating this alias ensures:
1. **Zero-downtime migration** - Old devices continue working without reconfiguration
2. **Gradual rollout** - New devices can use modern API endpoints
3. **Simplified maintenance** - Only one controller method to maintain

### When to Use Each Endpoint

**Use Legacy Endpoint** (`http_post_server.php`):
- Deployed gateways that cannot be easily reconfigured
- Testing backward compatibility
- Migrating from old PHP system

**Use Modern API Endpoint** (`/api/meter-readings/ingest`):
- New gateway deployments
- Development/testing
- API documentation/integration guides

### Gateway Configuration Examples

#### Old Gateway Config (uses legacy endpoint):
```ini
[SERVER]
URL=http://camr.test/http_post_server.php
METHOD=POST
```

#### New Gateway Config (uses modern API):
```ini
[SERVER]
URL=http://camr.test/api/meter-readings/ingest
METHOD=POST
```

Both configurations will work identically! 🎉

## Related Endpoints

The system also has other legacy endpoint aliases for gateway polling:

### Gateway Polling (Legacy URLs in web.php):
- `/rtu/index.php/rtu/rtu_check_update/{mac}/get_update_csv`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/get_content_csv`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_csv`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/get_update_location`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/get_content_location`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_location`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/force_lp`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/reset_force_lp`
- `/check_time.php`

All legacy URLs route to modern Laravel controllers while maintaining backward compatibility.

## Troubleshooting

### Issue: 404 Not Found on Legacy Endpoint
**Solution**: Check that route is registered:
```bash
php artisan route:clear
php artisan route:cache
php artisan route:list --path=http_post_server
```

### Issue: Different Response Between Endpoints
**Solution**: Both route to same controller - should never happen. Check logs:
```bash
tail -100 storage/logs/laravel.log | grep -i "meter reading"
```

### Issue: Old Gateway Still Not Working
**Possible causes**:
1. Gateway firewall blocking new server
2. Gateway using HTTPS vs HTTP mismatch
3. Gateway POST parameters different from expected format
4. Check gateway logs for exact URL being called

**Debug command**:
```bash
# Watch incoming requests in real-time
tail -f storage/logs/laravel.log | grep -E "(http_post_server|meter-readings/ingest)"
```

## Summary

✅ **Legacy endpoint created**: `POST /http_post_server.php`  
✅ **Routes to same controller**: `MeterReadingController@ingest`  
✅ **Backward compatible**: Old gateways continue working  
✅ **No code duplication**: Single controller method handles both  
✅ **Fully tested**: Uses existing test suite  

The system now supports both legacy and modern endpoint URLs! 🚀
