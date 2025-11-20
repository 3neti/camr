# Analytics API Documentation

## Overview

The Analytics API provides comprehensive endpoints for meter data visualization, reporting, and analysis. All endpoints return JSON responses and support filtering by site, meters, and date ranges.

**Base URL**: `http://camr.test/api/analytics`  
**Authentication**: Required (Laravel session via web middleware)

---

## Endpoints

### 1. Real-time Power

Get current power consumption and electrical parameters for active meters.

**Endpoint**: `GET /api/analytics/realtime-power`

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `site_id` | integer | No | Filter by specific site |
| `meter_ids` | array | No | Filter by specific meter IDs |
| `meter_ids[]` | integer | No | Individual meter ID (repeat for multiple) |

**Response**:
```json
{
  "timestamp": "2025-11-20T06:00:00+00:00",
  "meters": [
    {
      "meter_id": 153,
      "meter_name": "15003658",
      "power_kw": 13.88,
      "voltage": {
        "a": 219.4,
        "b": 223.6,
        "c": 224.9,
        "avg": 222.63
      },
      "current": {
        "a": 28.7,
        "b": 14.1,
        "c": 22.2,
        "avg": 21.67
      },
      "power_factor": 0.98,
      "frequency": 60.1,
      "timestamp": "2025-11-18T18:30:09+00:00",
      "is_recent": true
    }
  ],
  "summary": {
    "total_power_kw": 13.88,
    "meter_count": 1,
    "online_count": 1
  }
}
```

**Example Requests**:
```bash
# All meters
curl "http://camr.test/api/analytics/realtime-power"

# Specific site
curl "http://camr.test/api/analytics/realtime-power?site_id=1"

# Specific meters
curl "http://camr.test/api/analytics/realtime-power?meter_ids[]=153&meter_ids[]=154"
```

---

### 2. Energy Trend

Get energy consumption trends over time with flexible aggregation periods.

**Endpoint**: `GET /api/analytics/energy-trend`

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `period` | string | Yes | Aggregation period: `hourly`, `daily`, `weekly`, `monthly` |
| `start_date` | date | Yes | Start date (YYYY-MM-DD) |
| `end_date` | date | Yes | End date (YYYY-MM-DD) |
| `site_id` | integer | No | Filter by site |
| `meter_ids` | array | No | Filter by specific meters |

**Response**:
```json
{
  "period_type": "daily",
  "start_date": "2025-11-01",
  "end_date": "2025-11-20",
  "data": [
    {
      "period": "2025-11-18",
      "total_energy_kwh": 1200.45,
      "avg_power_kw": 50.02,
      "max_power_kw": 85.5,
      "meter_count": 5
    },
    {
      "period": "2025-11-19",
      "total_energy_kwh": 1180.32,
      "avg_power_kw": 49.18,
      "max_power_kw": 82.3,
      "meter_count": 5
    }
  ]
}
```

**Example Requests**:
```bash
# Daily trend for last 7 days
curl "http://camr.test/api/analytics/energy-trend?period=daily&start_date=2025-11-13&end_date=2025-11-20"

# Hourly trend for today
curl "http://camr.test/api/analytics/energy-trend?period=hourly&start_date=2025-11-20&end_date=2025-11-20&site_id=1"

# Weekly trend for last month
curl "http://camr.test/api/analytics/energy-trend?period=weekly&start_date=2025-10-20&end_date=2025-11-20"

# Monthly trend for year
curl "http://camr.test/api/analytics/energy-trend?period=monthly&start_date=2025-01-01&end_date=2025-11-20"
```

---

### 3. Power Quality

Get detailed power quality metrics including voltage, current, power factor, and frequency.

**Endpoint**: `GET /api/analytics/power-quality`

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | date | Yes | Start date (YYYY-MM-DD) |
| `end_date` | date | Yes | End date (YYYY-MM-DD) |
| `site_id` | integer | No | Filter by site |
| `meter_ids` | array | No | Filter by specific meters |

**Response**:
```json
{
  "period": {
    "start": "2025-11-19",
    "end": "2025-11-20"
  },
  "total_readings": 1248,
  "voltage": {
    "avg": 222.5,
    "min": 215.2,
    "max": 228.7,
    "by_phase": {
      "a": 221.8,
      "b": 223.1,
      "c": 222.6
    }
  },
  "current": {
    "avg": 22.5,
    "min": 5.2,
    "max": 45.8,
    "by_phase": {
      "a": 23.1,
      "b": 21.8,
      "c": 22.6
    }
  },
  "power_factor": {
    "avg": 0.95,
    "min": 0.55,
    "max": 0.99,
    "low_pf_percentage": 12.5
  },
  "frequency": {
    "avg": 60.01,
    "min": 59.95,
    "max": 60.15
  }
}
```

**Example Requests**:
```bash
# Last 24 hours
curl "http://camr.test/api/analytics/power-quality?start_date=2025-11-19&end_date=2025-11-20"

# Last 7 days for specific site
curl "http://camr.test/api/analytics/power-quality?start_date=2025-11-13&end_date=2025-11-20&site_id=1"

# Specific date range and meters
curl "http://camr.test/api/analytics/power-quality?start_date=2025-11-18&end_date=2025-11-19&meter_ids[]=153"
```

---

### 4. Demand Analysis

Get maximum demand tracking and peak analysis.

**Endpoint**: `GET /api/analytics/demand-analysis`

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | date | Yes | Start date (YYYY-MM-DD) |
| `end_date` | date | Yes | End date (YYYY-MM-DD) |
| `site_id` | integer | No | Filter by site |
| `meter_ids` | array | No | Filter by specific meters |

**Response**:
```json
{
  "period": {
    "start": "2025-11-01",
    "end": "2025-11-20"
  },
  "summary": {
    "peak_demand_kw": 85.5,
    "avg_demand_kw": 52.3,
    "peak_kvar_demand": 15.2,
    "avg_kvar_demand": 8.5
  },
  "daily_peaks": [
    {
      "date": "2025-11-18",
      "peak_demand_kw": 82.3,
      "avg_demand_kw": 50.1
    },
    {
      "date": "2025-11-19",
      "peak_demand_kw": 85.5,
      "avg_demand_kw": 51.8
    }
  ]
}
```

**Example Requests**:
```bash
# Last 30 days
curl "http://camr.test/api/analytics/demand-analysis?start_date=2025-10-21&end_date=2025-11-20"

# This month for specific site
curl "http://camr.test/api/analytics/demand-analysis?start_date=2025-11-01&end_date=2025-11-20&site_id=1"
```

---

### 5. Site Aggregation

Get energy consumption aggregated by site, building, or location.

**Endpoint**: `GET /api/analytics/site-aggregation`

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `group_by` | string | Yes | Grouping level: `site`, `building`, `location` |
| `start_date` | date | Yes | Start date (YYYY-MM-DD) |
| `end_date` | date | Yes | End date (YYYY-MM-DD) |

**Response**:
```json
{
  "group_by": "site",
  "period": {
    "start": "2025-11-19",
    "end": "2025-11-20"
  },
  "data": [
    {
      "group_name": "SMSS",
      "group_id": 1,
      "site_code": "SMSS",
      "total_energy_kwh": 2850.75,
      "avg_power_kw": 118.78,
      "max_power_kw": 185.5,
      "meter_count": 12
    },
    {
      "group_name": "GPJ",
      "group_id": 2,
      "site_code": "GPJ",
      "total_energy_kwh": 1920.45,
      "avg_power_kw": 80.02,
      "max_power_kw": 125.3,
      "meter_count": 8
    }
  ],
  "summary": {
    "total_energy_kwh": 4771.2,
    "total_groups": 2,
    "total_meters": 20
  }
}
```

**Example Requests**:
```bash
# Group by site
curl "http://camr.test/api/analytics/site-aggregation?group_by=site&start_date=2025-11-19&end_date=2025-11-20"

# Group by building
curl "http://camr.test/api/analytics/site-aggregation?group_by=building&start_date=2025-11-01&end_date=2025-11-20"

# Group by location
curl "http://camr.test/api/analytics/site-aggregation?group_by=location&start_date=2025-11-19&end_date=2025-11-20"
```

---

### 6. Top Consumers

Get ranked list of highest energy consuming meters.

**Endpoint**: `GET /api/analytics/top-consumers`

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | date | Yes | Start date (YYYY-MM-DD) |
| `end_date` | date | Yes | End date (YYYY-MM-DD) |
| `limit` | integer | No | Number of results (1-100, default: 10) |
| `site_id` | integer | No | Filter by site |

**Response**:
```json
{
  "period": {
    "start": "2025-11-19",
    "end": "2025-11-20"
  },
  "data": [
    {
      "meter_name": "15003658",
      "energy_consumed_kwh": 245.82,
      "avg_power_kw": 10.24,
      "max_power_kw": 18.5,
      "avg_power_factor": 0.98
    },
    {
      "meter_name": "15007451",
      "energy_consumed_kwh": 198.45,
      "avg_power_kw": 8.27,
      "max_power_kw": 15.2,
      "avg_power_factor": 0.92
    }
  ]
}
```

**Example Requests**:
```bash
# Top 10 consumers (default)
curl "http://camr.test/api/analytics/top-consumers?start_date=2025-11-19&end_date=2025-11-20"

# Top 5 consumers
curl "http://camr.test/api/analytics/top-consumers?start_date=2025-11-19&end_date=2025-11-20&limit=5"

# Top 20 for specific site
curl "http://camr.test/api/analytics/top-consumers?start_date=2025-11-01&end_date=2025-11-20&limit=20&site_id=1"
```

---

## Error Responses

All endpoints return consistent error responses:

**Validation Error (422)**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "start_date": ["The start date field is required."],
    "period": ["The selected period is invalid."]
  }
}
```

**Not Found (404)**:
```json
{
  "message": "Site not found."
}
```

**Server Error (500)**:
```json
{
  "message": "Server error occurred."
}
```

---

## Postman Collection

Import this JSON into Postman to get all endpoints pre-configured:

```json
{
  "info": {
    "name": "CAMR Analytics API",
    "description": "Comprehensive analytics endpoints for meter data visualization",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {
      "key": "baseUrl",
      "value": "http://camr.test/api/analytics"
    }
  ],
  "item": [
    {
      "name": "Real-time Power",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{baseUrl}}/realtime-power",
          "host": ["{{baseUrl}}"],
          "path": ["realtime-power"],
          "query": [
            {
              "key": "site_id",
              "value": "1",
              "disabled": true
            },
            {
              "key": "meter_ids[]",
              "value": "153",
              "disabled": true
            }
          ]
        }
      }
    },
    {
      "name": "Energy Trend - Daily",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{baseUrl}}/energy-trend?period=daily&start_date=2025-11-13&end_date=2025-11-20",
          "host": ["{{baseUrl}}"],
          "path": ["energy-trend"],
          "query": [
            {
              "key": "period",
              "value": "daily"
            },
            {
              "key": "start_date",
              "value": "2025-11-13"
            },
            {
              "key": "end_date",
              "value": "2025-11-20"
            },
            {
              "key": "site_id",
              "value": "1",
              "disabled": true
            }
          ]
        }
      }
    },
    {
      "name": "Energy Trend - Hourly",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{baseUrl}}/energy-trend?period=hourly&start_date=2025-11-20&end_date=2025-11-20",
          "host": ["{{baseUrl}}"],
          "path": ["energy-trend"],
          "query": [
            {
              "key": "period",
              "value": "hourly"
            },
            {
              "key": "start_date",
              "value": "2025-11-20"
            },
            {
              "key": "end_date",
              "value": "2025-11-20"
            }
          ]
        }
      }
    },
    {
      "name": "Power Quality",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{baseUrl}}/power-quality?start_date=2025-11-19&end_date=2025-11-20",
          "host": ["{{baseUrl}}"],
          "path": ["power-quality"],
          "query": [
            {
              "key": "start_date",
              "value": "2025-11-19"
            },
            {
              "key": "end_date",
              "value": "2025-11-20"
            },
            {
              "key": "site_id",
              "value": "1",
              "disabled": true
            }
          ]
        }
      }
    },
    {
      "name": "Demand Analysis",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{baseUrl}}/demand-analysis?start_date=2025-11-01&end_date=2025-11-20",
          "host": ["{{baseUrl}}"],
          "path": ["demand-analysis"],
          "query": [
            {
              "key": "start_date",
              "value": "2025-11-01"
            },
            {
              "key": "end_date",
              "value": "2025-11-20"
            },
            {
              "key": "site_id",
              "value": "1",
              "disabled": true
            }
          ]
        }
      }
    },
    {
      "name": "Site Aggregation - By Site",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{baseUrl}}/site-aggregation?group_by=site&start_date=2025-11-19&end_date=2025-11-20",
          "host": ["{{baseUrl}}"],
          "path": ["site-aggregation"],
          "query": [
            {
              "key": "group_by",
              "value": "site"
            },
            {
              "key": "start_date",
              "value": "2025-11-19"
            },
            {
              "key": "end_date",
              "value": "2025-11-20"
            }
          ]
        }
      }
    },
    {
      "name": "Site Aggregation - By Building",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{baseUrl}}/site-aggregation?group_by=building&start_date=2025-11-01&end_date=2025-11-20",
          "host": ["{{baseUrl}}"],
          "path": ["site-aggregation"],
          "query": [
            {
              "key": "group_by",
              "value": "building"
            },
            {
              "key": "start_date",
              "value": "2025-11-01"
            },
            {
              "key": "end_date",
              "value": "2025-11-20"
            }
          ]
        }
      }
    },
    {
      "name": "Top Consumers",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{baseUrl}}/top-consumers?start_date=2025-11-19&end_date=2025-11-20&limit=10",
          "host": ["{{baseUrl}}"],
          "path": ["top-consumers"],
          "query": [
            {
              "key": "start_date",
              "value": "2025-11-19"
            },
            {
              "key": "end_date",
              "value": "2025-11-20"
            },
            {
              "key": "limit",
              "value": "10"
            },
            {
              "key": "site_id",
              "value": "1",
              "disabled": true
            }
          ]
        }
      }
    }
  ]
}
```

---

## Usage Tips

1. **Authentication**: All requests require authentication. Use Laravel Sanctum tokens or session authentication.

2. **Date Formats**: Always use `YYYY-MM-DD` format for dates.

3. **Filtering**: You can combine `site_id` and `meter_ids` filters for precise data selection.

4. **Performance**: For large date ranges, use coarser periods (weekly/monthly instead of hourly).

5. **Time Zones**: All timestamps returned in ISO 8601 format with timezone.

6. **Array Parameters**: When passing array parameters via query string, use `param[]=value` format.

---

## Rate Limiting

Currently no rate limiting is enforced. Consider implementing rate limiting for production:
- Recommended: 60 requests per minute per user
- Use Laravel's built-in throttle middleware

---

## Future Enhancements

Planned additions:
- WebSocket support for real-time updates
- GraphQL endpoint for flexible queries
- Data export (CSV, Excel, PDF)
- Caching layer for improved performance
- Pagination for large result sets
