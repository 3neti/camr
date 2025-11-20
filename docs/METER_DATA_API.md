# Meter Data HTTP POST API Documentation

## Overview

This document describes the legacy HTTP POST endpoint used by deployed IoT gateways and meters to transmit real-time energy readings to the CAMR system. This protocol is in active production use by thousands of devices and **must be maintained as-is** for backward compatibility.

**Source Code Reference**: `/Users/rli/Documents/DEC/backup/http/http_post_server.php`

---

## Architecture

### Data Flow

```
┌─────────────┐
│   Meters    │ (Physical energy meters at customer sites)
│  (Gateways) │
└──────┬──────┘
       │ HTTP POST (URL-encoded)
       │ Every ~15 minutes
       ├──────────────────────────────────┐
       ↓                                  ↓
┌──────────────┐                   ┌──────────────┐
│   Gateway    │                   │  Individual  │
│  (Collects   │                   │    Meters    │
│   multiple   │                   │              │
│   meters)    │                   │              │
└──────┬───────┘                   └──────┬───────┘
       │                                  │
       ↓                                  ↓
┌─────────────────────────────────────────────────┐
│         HTTP POST Endpoint (Laravel)            │
│     POST /api/meter-readings/ingest             │
└─────────────────────────────────────────────────┘
       │
       ↓
┌─────────────────────────────────────────────────┐
│          meter_data Table (SQLite)              │
│    Stores timestamped energy measurements       │
└─────────────────────────────────────────────────┘
       │
       ↓ (Conditional: 2x daily at 12AM/12PM)
┌─────────────────────────────────────────────────┐
│        meters Table - Update Metadata           │
│   last_log_update, firmware version (soft_rev)  │
└─────────────────────────────────────────────────┘
```

### Transmission Frequency
- **Typical**: Every 15 minutes per meter
- **Observed**: Can vary by gateway configuration
- **Sample timestamps**: 18:27:00, 18:30:05, 18:30:09

---

## HTTP POST Protocol

### Endpoint
```
POST /api/meter-readings/ingest
Content-Type: application/x-www-form-urlencoded
```

### Authentication
**None** - The legacy system has no authentication mechanism. Devices POST directly without tokens or credentials.

### Request Format
Standard URL-encoded form data (application/x-www-form-urlencoded)

---

## POST Parameters

### Control Parameters

| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| `save_to_meter_data` | int | ✅ | Flag: 1=save reading, 0=skip (returns OK without saving) | `1` |
| `location` | string | ✅ | Site/location identifier | `SMSS` |
| `meter_id` | string | ✅ | Unique meter identifier | `15003658` |
| `datetime` | string | ✅ | Reading timestamp in format: `YYYY-MM-DD HH:MM:SS` (may be URL-encoded as `%20`) | `2025-11-18 18:30:09` |

### Voltage Measurements (3-Phase)

| Parameter | Type | Unit | Default | Description |
|-----------|------|------|---------|-------------|
| `vrms_a` | float | V | 0 | Phase A RMS voltage |
| `vrms_b` | float | V | 0 | Phase B RMS voltage |
| `vrms_c` | float | V | 0 | Phase C RMS voltage |

### Current Measurements (3-Phase)

| Parameter | Type | Unit | Default | Description |
|-----------|------|------|---------|-------------|
| `irms_a` | float | A | 0 | Phase A RMS current |
| `irms_b` | float | A | 0 | Phase B RMS current |
| `irms_c` | float | A | 0 | Phase C RMS current |

### Power Measurements

| Parameter | Type | Unit | Default | Description |
|-----------|------|------|---------|-------------|
| `watt` | float | kW | 0 | Real power (instantaneous) |
| `va` | float | kVA | 0 | Apparent power |
| `var` | float | kVAR | 0 | Reactive power |
| `pf` | float | - | 0 | Power factor (0.0 to 1.0) |
| `freq` | float | Hz | 0 | Line frequency |

### Energy Accumulators (Watt-hours)

| Parameter | Type | Unit | Default | Description |
|-----------|------|------|---------|-------------|
| `wh_del` | float | kWh | 0 | Energy delivered (consumed from grid) |
| `wh_rec` | float | kWh | 0 | Energy received (exported to grid) |
| `wh_net` | float | kWh | 0 | Net energy (delivered - received) |
| `wh_total` | float | kWh | 0 | Total cumulative energy |

### Energy Accumulators (VAR-hours)

| Parameter | Type | Unit | Default | Description |
|-----------|------|------|---------|-------------|
| `varh_neg` | float | kVARh | 0 | Negative reactive energy |
| `varh_pos` | float | kVARh | 0 | Positive reactive energy |
| `varh_net` | float | kVARh | 0 | Net reactive energy |
| `varh_total` | float | kVARh | 0 | Total cumulative reactive energy |
| `vah_total` | float | kVAh | 0 | Total cumulative apparent energy |

### Demand Values (Maximum Demand)

| Parameter | Type | Unit | Default | Description |
|-----------|------|------|---------|-------------|
| `max_rec_kw_dmd` | float | kW | null | Maximum received kW demand |
| `max_rec_kw_dmd_time` | datetime | - | `0000-00-00 00:00:00` | Timestamp of max received demand |
| `max_del_kw_dmd` | float | kW | null | Maximum delivered kW demand |
| `max_del_kw_dmd_time` | datetime | - | `0000-00-00 00:00:00` | Timestamp of max delivered demand |
| `max_pos_kvar_dmd` | float | kVAR | null | Maximum positive kVAR demand |
| `max_pos_kvar_dmd_time` | datetime | - | `0000-00-00 00:00:00` | Timestamp of max positive demand |
| `max_neg_kvar_dmd` | float | kVAR | null | Maximum negative kVAR demand |
| `max_neg_kvar_dmd_time` | datetime | - | `0000-00-00 00:00:00` | Timestamp of max negative demand |

### Phase Angle Measurements

| Parameter | Type | Unit | Default | Description |
|-----------|------|------|---------|-------------|
| `v_ph_angle_a` | float | degrees | 0 | Phase A voltage angle |
| `v_ph_angle_b` | float | degrees | 0 | Phase B voltage angle |
| `v_ph_angle_c` | float | degrees | 0 | Phase C voltage angle |
| `i_ph_angle_a` | float | degrees | 0 | Phase A current angle |
| `i_ph_angle_b` | float | degrees | 0 | Phase B current angle |
| `i_ph_angle_c` | float | degrees | 0 | Phase C current angle |

**Note**: Legacy code has typo on line 65: `i_ph_angle_b` reads from `$_POST['i_ph_angle_c']` instead of `$_POST['i_ph_angle_b']`

### Device Metadata

| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| `mac_address` | string | Optional | Gateway MAC address | `AA:BB:CC:DD:EE:FF` |
| `soft_rev` | string | Optional | Firmware/software revision | `2.10` |
| `relay_status` | int | Optional | Relay on/off status | `0` or `1` |

---

## Sample POST Request

### Raw POST Data (URL-encoded)
```
save_to_meter_data=1
&location=SMSS
&meter_id=15003658
&datetime=2025-11-18%2018:30:09
&vrms_a=219.4
&vrms_b=223.6
&vrms_c=224.9
&irms_a=28.7
&irms_b=14.1
&irms_c=22.2
&freq=60.1
&pf=0.98
&watt=13.88
&va=14.09
&var=2.44
&wh_rec=0.0000
&wh_del=0.0000
&wh_net=0.0000
&wh_total=61450.9102
&varh_neg=0.0000
&varh_pos=0.0000
&varh_net=0.0000
&varh_total=13281.0000
&vah_total=0.0000
&max_rec_kw_dmd=0.000
&max_rec_kw_dmd_time=
&max_del_kw_dmd=0.000
&max_del_kw_dmd_time=
&max_pos_kvar_dmd=0.000
&max_pos_kvar_dmd_time=
&max_neg_kvar_dmd=0.000
&max_neg_kvar_dmd_time=
&vpha_a=0.000
&vpha_b=0.000
&vpha_c=0.000
&ipha_a=0.000
&ipha_b=0.000
&ipha_c=0.000
&soft_rev=2.10
```

### Decoded Values
- **Location**: SMSS (likely "SM San Mateo" or similar site code)
- **Meter ID**: 15003658
- **Timestamp**: 2025-11-18 18:30:09 (6:30 PM, Nov 18, 2025)
- **3-Phase Voltage**: 219.4V, 223.6V, 224.9V (balanced ~220V system)
- **3-Phase Current**: 28.7A, 14.1A, 22.2A
- **Power**: 13.88 kW at 0.98 power factor
- **Cumulative Energy**: 61,450.91 kWh total

---

## Response Format

### Success Response
```
OK, 2025-11-18 18:30:15
```

**Format**: `OK, {server_timestamp}`

Where `{server_timestamp}` is the server's current datetime in format: `YYYY-MM-DD HH:MM:SS`

### Response Conditions

1. **If `save_to_meter_data=1`**: Data is inserted into `meter_data` table, returns "OK" with server time
2. **If `save_to_meter_data=0`**: No data saved, but still returns "OK" with server time
3. **On any error**: Still returns "OK" (errors are silently ignored in production)

---

## Database Operations

### Primary Operation: Insert to `meter_data`

The endpoint inserts a new row into the `meter_data` table with all received parameters.

**Legacy Schema** (from `http_post_server.php`):
```sql
INSERT INTO meter_data (
    location,
    meter_id,
    datetime,
    vrms_a, vrms_b, vrms_c,
    irms_a, irms_b, irms_c,
    freq, pf, watt, va, var,
    wh_del, wh_rec, wh_net, wh_total,
    varh_neg, varh_pos, varh_net, varh_total, vah_total,
    max_rec_kw_dmd, max_rec_kw_dmd_time,
    max_del_kw_dmd, max_del_kw_dmd_time,
    max_pos_kvar_dmd, max_pos_kvar_dmd_time,
    max_neg_kvar_dmd, max_neg_kvar_dmd_time,
    v_ph_angle_a, v_ph_angle_b, v_ph_angle_c,
    i_ph_angle_a, i_ph_angle_b, i_ph_angle_c,
    mac_addr, soft_rev, relay_status
) VALUES (...)
```

### Secondary Operation: Update Meter Metadata

**Conditions** (ALL must be true):
1. Reading datetime is in the **current hour** (server time)
2. Server time is **00:00-00:14** OR **12:00-12:14** (twice daily)
3. Meter must exist in database and be linked to gateway via MAC address

**Update Query**:
```sql
UPDATE meter_details t1
JOIN meter_rtu t2 ON t1.rtu_sn_number_id = t2.id
SET 
    t1.last_log_update = '{datetime}',
    t1.soft_rev = '{soft_rev}'
WHERE 
    t1.meter_name = '{meter_id}'
    AND t1.meter_site_name = '{location}'
    AND t2.mac_addr = '{mac_address}'
```

**Purpose**: Track which meters are actively reporting and update firmware versions

---

## Schema Mapping: Legacy → Laravel

### Current Laravel Schema (`meter_data` table)

| Laravel Column | Type | Legacy Field | Mapping Notes |
|---------------|------|--------------|---------------|
| `id` | bigint | - | Auto-increment primary key |
| `meter_id` | bigint | `meter_id` | **⚠️ TYPE CHANGE**: Legacy is string, Laravel is foreign key to `meters.id` |
| `reading_datetime` | datetime | `datetime` | **⚠️ COLUMN NAME**: Must map `datetime` → `reading_datetime` |
| `vrms_a` | decimal(10,4) | `vrms_a` | ✅ Direct map |
| `vrms_b` | decimal(10,4) | `vrms_b` | ✅ Direct map |
| `vrms_c` | decimal(10,4) | `vrms_c` | ✅ Direct map |
| `irms_a` | decimal(10,4) | `irms_a` | ✅ Direct map |
| `irms_b` | decimal(10,4) | `irms_b` | ✅ Direct map |
| `irms_c` | decimal(10,4) | `irms_c` | ✅ Direct map |
| `freq` | decimal(5,2) | `freq` | ✅ Direct map |
| `pf` | decimal(5,4) | `pf` | ✅ Direct map |
| `watt` | decimal(12,4) | `watt` | ✅ Direct map |
| `va` | decimal(12,4) | `va` | ✅ Direct map |
| `var` | decimal(12,4) | `var` | ✅ Direct map |
| `wh_delivered` | decimal(15,4) | `wh_del` | ✅ Direct map |
| `wh_received` | decimal(15,4) | `wh_rec` | ✅ Direct map |
| `wh_net` | decimal(15,4) | `wh_net` | ✅ Direct map |
| `wh_total` | decimal(15,4) | `wh_total` | ✅ Direct map |
| `varh_negative` | decimal(15,4) | `varh_neg` | ✅ Direct map |
| `varh_positive` | decimal(15,4) | `varh_pos` | ✅ Direct map |
| `varh_net` | decimal(15,4) | `varh_net` | ✅ Direct map |
| `varh_total` | decimal(15,4) | `varh_total` | ✅ Direct map |
| `vah_total` | decimal(15,4) | `vah_total` | ✅ Direct map |
| `max_rec_kw_dmd` | decimal(12,4) | `max_rec_kw_dmd` | ✅ Direct map |
| `max_rec_kw_dmd_time` | datetime | `max_rec_kw_dmd_time` | ✅ Direct map |
| `max_del_kw_dmd` | decimal(12,4) | `max_del_kw_dmd` | ✅ Direct map |
| `max_del_kw_dmd_time` | datetime | `max_del_kw_dmd_time` | ✅ Direct map |
| `max_pos_kvar_dmd` | decimal(12,4) | `max_pos_kvar_dmd` | ✅ Direct map |
| `max_pos_kvar_dmd_time` | datetime | `max_pos_kvar_dmd_time` | ✅ Direct map |
| `max_neg_kvar_dmd` | decimal(12,4) | `max_neg_kvar_dmd` | ✅ Direct map |
| `max_neg_kvar_dmd_time` | datetime | `max_neg_kvar_dmd_time` | ✅ Direct map |
| `v_ph_angle_a` | decimal(6,3) | `v_ph_angle_a` | ✅ Direct map |
| `v_ph_angle_b` | decimal(6,3) | `v_ph_angle_b` | ✅ Direct map |
| `v_ph_angle_c` | decimal(6,3) | `v_ph_angle_c` | ✅ Direct map |
| `i_ph_angle_a` | decimal(6,3) | `i_ph_angle_a` | ✅ Direct map |
| `i_ph_angle_b` | decimal(6,3) | `i_ph_angle_b` | ✅ Direct map |
| `i_ph_angle_c` | decimal(6,3) | `i_ph_angle_c` | ✅ Direct map |
| `created_at` | timestamp | - | Auto-managed by Laravel |
| `updated_at` | timestamp | - | Auto-managed by Laravel |

### Missing Fields (Not in Current Schema)

| Legacy Field | Type | Purpose | Recommendation |
|-------------|------|---------|----------------|
| `location` | string | Site identifier | **Map to `meter.site_id`** via lookup |
| `mac_addr` | string | Gateway MAC address | **Store in `gateways` table**, link via meter |
| `soft_rev` | string | Firmware version | **Add to `meters` table** as `firmware_version` |
| `relay_status` | int | Relay on/off | **Add to `meter_data` table** if needed for monitoring |

### Critical Mapping Challenges

#### 1. **`meter_id` Resolution**
- **Legacy**: String identifier like "15003658" (serial number)
- **Laravel**: Foreign key integer referencing `meters.id`
- **Solution**: Must lookup meter by `meter_number` or `serial_number` field

```php
$meter = Meter::where('meter_number', $request->input('meter_id'))->first();
if (!$meter) {
    // Log warning, optionally auto-create meter
}
```

#### 2. **`location` Resolution**
- **Legacy**: String code like "SMSS"
- **Laravel**: Sites are referenced by `site_id` via `meters.site_id`
- **Solution**: Either:
  - Pre-seed mapping table: `site_code` → `site_id`
  - Store in `sites.code` field for lookup

#### 3. **`datetime` Column Name**
- **Legacy**: Uses `datetime` field name
- **Laravel**: Uses `reading_datetime` field name
- **Solution**: Map parameter during insert

#### 4. **Missing Metadata Fields**
- `mac_address`: Should update `gateways.mac_address`
- `soft_rev`: Should update `meters.firmware_version`
- `relay_status`: Currently not stored anywhere

---

## Implementation Requirements

### Must-Have Features

1. **Exact Protocol Match**: Accept all legacy POST parameters with exact names
2. **URL-encoded Support**: Handle `%20` and other URL encoding
3. **Default Values**: Apply defaults for missing optional fields (e.g., `0` for numeric fields)
4. **Response Format**: Return exactly `OK, {server_timestamp}` in plain text
5. **Meter Resolution**: Lookup `meters.id` from incoming `meter_id` string
6. **Site Resolution**: Lookup site via `location` parameter
7. **Datetime Mapping**: Map `datetime` → `reading_datetime`
8. **Conditional Metadata Update**: Implement 12AM/12PM update logic

### Should-Have Features

1. **Error Logging**: Log failed inserts for debugging (don't break response)
2. **Missing Meter Handling**: Decide behavior when meter not found (skip? auto-create?)
3. **Duplicate Detection**: Check for duplicate readings (same meter + timestamp)
4. **Queue Processing**: Move heavy DB operations to background queue
5. **Metrics**: Track ingestion rate, error rate, data latency

### Optional Enhancements

1. **Rate Limiting**: Protect against abuse (per IP or per meter)
2. **Data Validation**: Validate ranges (e.g., voltage 0-500V, current 0-10000A)
3. **Timestamp Validation**: Reject future timestamps or very old data
4. **Gateway Tracking**: Create/update `gateways` records from `mac_address`

---

## Recommended Implementation Approach

### Phase 1: Core Endpoint (Week 1)

**Goal**: Accept legacy POST requests and insert to database

1. Create API route that matches legacy protocol
2. Accept all 38+ POST parameters
3. Implement meter lookup by `meter_number`
4. Map fields to Laravel schema
5. Insert to `meter_data` table
6. Return "OK, {timestamp}" response
7. Add comprehensive error logging

**Files to Create**:
- `routes/api.php` - Define route
- `app/Http/Controllers/Api/MeterReadingController.php` - Handle request
- `app/Http/Requests/MeterReadingRequest.php` - Optional validation
- `tests/Feature/MeterReadingApiTest.php` - Integration tests

### Phase 2: Metadata Updates (Week 2)

**Goal**: Implement conditional meter metadata updates

1. Add conditional logic for 12AM/12PM updates
2. Update `meters.last_log_update` field
3. Update `meters.firmware_version` from `soft_rev`
4. Handle gateway linking via `mac_address`

### Phase 3: Production Deployment

**Prerequisites**:
1. Extensive testing with sample POST data
2. Verify response format exactly matches legacy
3. Performance testing (can handle 1000s of requests/hour)
4. Monitoring and alerting setup

**Migration Path**:
1. Deploy new endpoint to production server
2. Update gateway configuration to point to new endpoint
3. Run both old and new endpoints in parallel (validation period)
4. Monitor for discrepancies
5. Gradually migrate gateways to new endpoint
6. Decommission old PHP endpoint

---

## Testing Strategy

### Unit Tests

Test individual components:
- Field mapping logic
- Meter lookup resolution
- Datetime parsing and validation
- Default value application

### Integration Tests

Test complete flow:
```php
// Test successful insertion
$response = $this->post('/api/meter-readings/ingest', [
    'save_to_meter_data' => 1,
    'location' => 'SMSS',
    'meter_id' => '15003658',
    'datetime' => '2025-11-18 18:30:09',
    'vrms_a' => 219.4,
    // ... all fields
]);

$response->assertStatus(200);
$response->assertSee('OK,');
$this->assertDatabaseHas('meter_data', [
    'meter_id' => $meter->id,
    'reading_datetime' => '2025-11-18 18:30:09',
]);
```

### Load Testing

Simulate production traffic:
- 1000 requests/minute (typical)
- Burst to 5000 requests/minute (stress test)
- Measure response time (<100ms target)
- Monitor database connection pool

### Validation Testing

Compare legacy vs. new endpoint:
- Send same POST data to both
- Verify database records are identical
- Confirm response format matches exactly

---

## Performance Considerations

### Expected Load

- **Active Meters**: ~1,000 to 10,000 devices
- **Reporting Frequency**: Every 15 minutes
- **Peak Request Rate**: ~1,000 requests/minute (if synchronized)
- **Daily Inserts**: 96,000 to 960,000 records/day

### Optimization Strategies

1. **Database Indexing**:
   ```sql
   INDEX on meter_data(meter_id, reading_datetime)
   INDEX on meters(meter_number) -- for lookup
   ```

2. **Connection Pooling**: Use persistent database connections

3. **Queue Processing**: 
   - Accept request immediately
   - Queue actual insert to background job
   - Return "OK" response without waiting for DB

4. **Batch Inserts**: Collect multiple readings and insert in batches (if timing allows)

5. **Read Replicas**: If queries slow down due to writes, use read replicas for dashboard

---

## Security Considerations

### Known Vulnerabilities (Accepted for Compatibility)

1. ❌ **No Authentication**: Anyone can POST data
2. ❌ **No Authorization**: Cannot verify device identity
3. ❌ **No Encryption**: Data sent over HTTP (not HTTPS)
4. ❌ **No Request Signing**: Cannot verify data integrity
5. ❌ **SQL Injection**: Legacy code vulnerable (mitigated in Laravel via Eloquent)
6. ❌ **No Rate Limiting**: Potential DoS vector

### Mitigation Strategies (Without Breaking Protocol)

1. **IP Whitelisting**: Only accept requests from known gateway IPs (if feasible)
2. **Rate Limiting**: Limit requests per IP (e.g., 100/minute)
3. **Input Sanitization**: Validate/sanitize all inputs (Laravel does this automatically)
4. **Monitoring**: Alert on suspicious patterns (unusual meters, invalid data)
5. **Network Segmentation**: Isolate API endpoint from internal systems
6. **Database Permissions**: Use read-only user for queries, separate write user

### Acceptable Risk

Given the constraints (cannot modify device firmware), these vulnerabilities are **accepted operational risks**. The system should focus on:
- Availability (uptime > 99.9%)
- Data integrity (prevent bad data from corrupting database)
- Monitoring (detect and alert on anomalies)

---

## Monitoring and Alerting

### Key Metrics

1. **Request Rate**: Requests per minute (should be steady ~1000/min)
2. **Error Rate**: Failed inserts / total requests (target <0.1%)
3. **Response Time**: P50, P95, P99 latency (target <100ms)
4. **Unknown Meters**: Count of requests with unrecognized `meter_id`
5. **Data Gaps**: Meters that haven't reported in >30 minutes
6. **Duplicate Readings**: Same meter + timestamp received multiple times

### Alerting Rules

- ⚠️ Request rate drops by >50% (possible network issue)
- 🚨 Error rate exceeds 1% (database problem)
- ⚠️ Response time P95 > 500ms (performance degradation)
- 🚨 Endpoint down (returns 500/503 for >5 minutes)
- ⚠️ >10 unknown meters in 1 hour (configuration issue)

---

## Appendix A: Sample Payloads

### Sample 1: Normal Operation (High Power Factor)
```
Meter: 15003658
Location: SMSS
Timestamp: 2025-11-18 18:30:09
Power: 13.88 kW at PF 0.98 (good)
Voltage: 219V / 224V / 225V (balanced)
Current: 29A / 14A / 22A
Energy: 61,450 kWh total
```

### Sample 2: Poor Power Factor
```
Meter: 15007451
Location: SMSS
Timestamp: 2025-11-18 18:30:05
Power: 6.34 kW at PF 0.55 (poor - needs correction)
Voltage: 219V / 223V / 225V
Current: 17A / 18A / 18A
Energy: 23,945 kWh total
Reactive: 32,381 kVARh (high reactive power)
```

---

## Appendix B: Database Schema Changes Required

### Add to `meters` table:
```sql
ALTER TABLE meters ADD COLUMN firmware_version VARCHAR(20) AFTER model;
ALTER TABLE meters ADD COLUMN last_log_update DATETIME NULL AFTER firmware_version;
```

### Add to `meter_data` table (optional):
```sql
ALTER TABLE meter_data ADD COLUMN relay_status TINYINT DEFAULT 0 AFTER i_ph_angle_c;
```

### Add to `gateways` table (if not exists):
```sql
ALTER TABLE gateways ADD COLUMN mac_address VARCHAR(17) UNIQUE AFTER name;
```

---

## Appendix C: Migration Checklist

- [ ] Document all existing gateway IP addresses
- [ ] Create meter number → meter ID mapping
- [ ] Create location code → site ID mapping
- [ ] Implement Laravel endpoint with exact protocol match
- [ ] Test with sample POST data from files
- [ ] Deploy to staging environment
- [ ] Configure one test gateway to use new endpoint
- [ ] Monitor for 24 hours, compare data
- [ ] Gradually migrate gateways (10% → 50% → 100%)
- [ ] Monitor error rates and data consistency
- [ ] Archive legacy PHP code (do not delete)

---

**Document Version**: 1.0  
**Last Updated**: 2025-11-19  
**Author**: WARP Agent  
**Status**: Ready for Implementation
