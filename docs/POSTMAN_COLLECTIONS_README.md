# CAMR Postman Collections

## 📦 Complete Collection (Recommended)

**File**: `CAMR_Complete_API.postman_collection.json`

This is the **consolidated collection** containing all CAMR API endpoints organized into 4 main categories:

### Collection Structure (40+ Requests)

1. **📊 Analytics API** (7 requests)
   - Real-time Power
   - Energy Trend (Daily, Hourly)
   - Power Quality
   - Demand Analysis
   - Site Aggregation
   - Top Consumers

2. **⚡ Meter Reading Ingestion** (4 requests)
   - Modern API endpoints
   - Legacy PHP endpoints (`/http_post_server.php`)
   - Sample data with high/poor power factor
   - Connection testing

3. **🔄 Gateway Polling - Modern API** (10 requests)
   - CSV Meter List Updates (3-step workflow)
   - Site Code Updates (3-step workflow)
   - Force Load Profile (2-step workflow)
   - Server Time Synchronization

4. **🔧 Gateway Polling - Legacy PHP** (10 requests)
   - Same workflows as modern API
   - Uses legacy `/rtu/index.php/...` URLs
   - Maintains backward compatibility

### Variables
- `{{baseUrl}}` = `http://camr.test`
- `{{gatewayMac}}` = `4e:b8:61:88:74:4f`
- `{{current_datetime}}` = Auto-generated timestamp (pre-request script)

### Pre-Request Script
Automatically generates current timestamp in `YYYY-MM-DD HH:MM:SS` format for meter reading requests.

---

---

## 🚀 Quick Start

### Import Complete Collection
1. Open Postman
2. Click **Import**
3. Select `CAMR_Complete_API.postman_collection.json`
4. Click **Import**

### Test Endpoints
```bash
# 1. Test Analytics (requires authentication)
GET http://camr.test/api/analytics/realtime-power

# 2. Test Meter Ingestion (modern)
POST http://camr.test/api/meter-readings/ingest

# 3. Test Meter Ingestion (legacy)
POST http://camr.test/http_post_server.php

# 4. Test Gateway Polling (modern)
GET http://camr.test/api/gateway/4e:b8:61:88:74:4f/check/csv

# 5. Test Gateway Polling (legacy)
GET http://camr.test/rtu/index.php/rtu/rtu_check_update/4e:b8:61:88:74:4f/get_update_csv
```

---

## 🔑 Authentication

### No Authentication Required
- ✅ Meter Reading Ingestion (both modern & legacy)
- ✅ Gateway Polling (all endpoints)
- ✅ Server Time Synchronization

**Why**: IoT devices cannot handle OAuth/session authentication. Security via network-level controls (VPN, firewall).

### Session Authentication Required
- ✅ Analytics API endpoints

**How to authenticate**:
1. Login to CAMR web UI: `http://camr.test`
2. Use same browser/session for Postman requests
3. Or use Postman Interceptor extension

---

## 📝 Endpoint Categories

### IoT Device Endpoints (No Auth)
```
POST /api/meter-readings/ingest
POST /http_post_server.php                    (legacy)

GET  /api/gateway/{mac}/check/csv
GET  /api/gateway/{mac}/csv
POST /api/gateway/{mac}/csv/reset
GET  /api/gateway/{mac}/check/site-code
GET  /api/gateway/{mac}/site-code
POST /api/gateway/{mac}/site-code/reset
GET  /api/gateway/{mac}/check/load-profile
POST /api/gateway/{mac}/load-profile/reset
GET  /api/server-time

GET  /rtu/index.php/rtu/rtu_check_update/{mac}/...  (legacy)
GET  /check_time.php                                 (legacy)
```

### Web Application Endpoints (Auth Required)
```
GET /api/analytics/realtime-power
GET /api/analytics/energy-trend
GET /api/analytics/power-quality
GET /api/analytics/demand-analysis
GET /api/analytics/site-aggregation
GET /api/analytics/top-consumers
```

---

## 🛠️ Testing Workflows

### Test Meter Data Ingestion
1. Open: "Meter Reading Ingestion" → "Modern API - High Power Factor Sample"
2. Click **Send**
3. Expected Response: `OK`
4. Verify in database: Check `meter_data` table

### Test Gateway Polling (3-Step CSV Update)
1. **Step 1**: Check if update needed
   - Send: "Gateway Polling - Modern API" → "CSV Meter List Updates" → "1. Check CSV Update Required"
   - Response: `1` (update needed) or `0` (no update)

2. **Step 2**: Download CSV (if `1` was returned)
   - Send: "2. Download CSV Meter List"
   - Response: CSV content with meter list

3. **Step 3**: Acknowledge completion
   - Send: "3. Reset CSV Update Flag"
   - Response: `OK`

### Test Backward Compatibility
1. Send modern endpoint: `POST /api/meter-readings/ingest`
2. Send legacy endpoint: `POST /http_post_server.php`
3. Both should return `OK` and create identical database records

---

## 🔄 Migration Path

### Current State
- ✅ Modern API endpoints active
- ✅ Legacy PHP endpoints active (backward compatibility)
- ✅ Both route to same controllers
- ✅ Identical behavior

### For New Deployments
Use **modern API endpoints**:
- `/api/meter-readings/ingest`
- `/api/gateway/{mac}/...`
- `/api/server-time`

### For Existing Deployments
Continue using **legacy endpoints** until firmware update:
- `/http_post_server.php`
- `/rtu/index.php/rtu/rtu_check_update/{mac}/...`
- `/check_time.php`

---

## 📖 Related Documentation

- **`docs/METER_DATA_API.md`** - Complete meter ingestion protocol
- **`docs/GATEWAY_POLLING_API.md`** - Gateway polling workflows
- **`docs/LEGACY_ENDPOINT_TEST.md`** - Legacy endpoint testing guide
- **`docs/ENDPOINT_MIGRATION_GUIDE.md`** - Migration strategy
- **`docs/SAP_INTEGRATION.md`** - SAP/ERP data sync
- **`docs/SAP_DEMO_SCRIPT.md`** - SAP integration demo

---

## 🎯 Summary

The `CAMR_Complete_API.postman_collection.json` contains **all CAMR API endpoints** in one organized collection:

✅ **40+ requests** covering Analytics, Meter Ingestion, and Gateway Polling  
✅ **Modern & Legacy endpoints** side-by-side for backward compatibility  
✅ **Well-organized** folder structure with emojis for easy navigation  
✅ **Auto-generated timestamps** via pre-request script  
✅ **Comprehensive documentation** for every endpoint  

**Import `CAMR_Complete_API.postman_collection.json` and start testing!** 🚀
