# CAMR TODO Items

## Root Endpoint Investigation

**Priority**: Low  
**Status**: Open  
**Created**: 2025-11-27

### Issue
User mentioned that `api/meter-readings/ingest` is actually accessed at root `/` instead of `/api/meter-readings/ingest`.

### Investigation Needed
- [ ] Check if there's a route alias at `/` pointing to meter ingestion
- [ ] Review deployed gateway configuration to confirm actual endpoint URL
- [ ] Check if there's URL rewriting happening (nginx/Apache config)
- [ ] Review legacy PHP code to see what URL was originally used
- [ ] Check if gateways are configured with full URL or just host + path

### Related Files
- `routes/api.php` - Current route: `POST /api/meter-readings/ingest`
- `routes/web.php` - Check for root route redirects
- `app/Http/Controllers/Api/MeterReadingController.php` - Ingestion controller
- `docs/METER_DATA_API.md` - API documentation

### Possible Solutions
1. Add route alias: `Route::post('/', [MeterReadingController::class, 'ingest'])`
2. Update gateway configuration (if possible)
3. Add web server rewrite rule
4. Keep both endpoints for backward compatibility

### Notes
- Do not break existing functionality
- Consider backward compatibility with deployed devices
- Test thoroughly before making changes to production endpoints
