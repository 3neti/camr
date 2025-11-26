# CAMR Domain Knowledge

## Overview

DEC CAMR (Centralized Automated Meter Reading) is a system for monitoring and managing automated meter reading infrastructure. The application provides a hierarchical structure for organizing and tracking meters, gateways, and their physical locations.

## Hierarchical Infrastructure Model

The CAMR system uses a strict hierarchy to organize infrastructure:

```
Site (campus/facility)
  └── Building (structure)
       └── Location (specific area)
            └── Gateway (communication device)
                 └── Meter (measuring device)
```

### Relationships

- **Site** → has many Buildings, Locations, Gateways, Meters
- **Building** → belongs to Site, has many Locations
- **Location** → belongs to Site, optionally belongs to Building, has many Gateways and Meters
- **Gateway** → belongs to Site and Location, has many Meters
- **Meter** → belongs to Site, Location, and Gateway

### Site Context Filtering

A key feature is the ability to select a site and have all related data automatically filtered:

- When a site is selected, it persists in the session across navigation
- All index pages (Buildings, Locations, Gateways, Meters) respect the selected site
- The filter is cleared by selecting "All Sites" in any dropdown
- Site selection is stored in the session and passed as props to Inertia pages

## Core Entities

### Site

The top-level organizational unit representing a physical location (campus, facility, property).

**Key Attributes:**
- `code` - Unique identifier (e.g., "SITE-001")
- `company_id` - Associated company
- `division_id` - Associated division
- `latitude`, `longitude` - Geographic coordinates
- Timestamps for tracking creation and updates

**Business Rules:**
- Code must be unique
- Company and division are optional but recommended
- Can have multiple buildings, locations, gateways, and meters

### Building

A structure within a site.

**Key Attributes:**
- `code` - Unique identifier within the site
- `name` - Human-readable name
- `site_id` - Parent site (required)
- Location information (address, city, country, postal)

**Business Rules:**
- Must belong to a site
- Can have multiple locations
- Code should be unique within a site

### Location

A specific area within a building or site where meters are installed.

**Key Attributes:**
- `code` - Unique identifier
- `name` - Human-readable name
- `site_id` - Parent site (required)
- `building_id` - Parent building (optional)
- Description of the location

**Business Rules:**
- Must belong to a site
- May optionally belong to a building
- Can have multiple gateways and meters

### Gateway

A communication device that collects data from meters.

**Key Attributes:**
- `code` - Unique identifier (e.g., gateway serial number)
- `name` - Human-readable name
- `site_id` - Parent site (required)
- `location_id` - Parent location (optional)
- `last_log_update` - Timestamp of last data transmission
- `status` - Computed attribute (online/offline)

**Status Logic:**
- A gateway is considered **online** if `last_log_update` is within the last 24 hours
- Otherwise, it's **offline**
- Status is computed dynamically using Laravel's Attribute accessor

```php
protected function status(): Attribute
{
    return Attribute::make(
        get: fn () => $this->last_log_update && 
            $this->last_log_update >= now()->subDay()
    );
}
```

**Business Rules:**
- Must belong to a site
- Should be assigned to a location
- Can have multiple meters connected
- Status updates automatically based on last_log_update

### Meter

The actual measuring device (electric, water, gas, etc.).

**Key Attributes:**
- `code` - Unique identifier (meter serial number)
- `name` - Human-readable name
- `site_id` - Parent site (required)
- `location_id` - Parent location (optional)
- `gateway_id` - Parent gateway (optional)
- `last_log_update` - Timestamp of last reading
- `status` - Computed attribute (online/offline)
- `type` - Meter type (electric, water, gas, etc.)
- `brand` - Manufacturer
- Customer information (name, account)

**Status Logic:**
- Same as Gateway: online if `last_log_update` is within 24 hours
- Status is computed dynamically

**Business Rules:**
- Must belong to a site
- Should be assigned to a location and gateway
- Type and brand are required for proper categorization
- Can have associated meter data readings

### Additional Entities

#### Company
- Represents organizations that own or operate sites
- Has many sites

#### Division
- Represents organizational divisions or departments
- Has many sites
- Allows grouping sites by business unit

#### ConfigurationFile
- Stores configuration files for gateways and meters
- Tracks file versions and upload history
- Associated with either a gateway or meter

#### MeterData
- Stores actual meter readings over time
- Linked to meters
- Contains reading values, timestamps, and metadata

#### LoadProfile
- Stores detailed load/consumption profiles
- Linked to meters
- Used for analytics and reporting

## Key Features

### Data Import System

Bulk import of meter data from external sources:
- Supports SQL files (up to 100MB)
- Supports CSV files (up to 100MB)
- Background processing with queue jobs
- Status tracking (Pending → Processing → Completed/Failed)
- Validation before import
- Cancellation support for pending/processing jobs

### Configuration File Management

- Upload and associate config files with gateways or meters
- Track configuration history
- Download and view configurations
- Version control for configurations

### Real-time Status Monitoring

- Dashboard shows online/offline status for gateways and meters
- Status indicators on all index pages
- Automatic status calculation based on last_log_update
- 24-hour threshold for considering devices online

### Analytics and Reporting

- Dashboard with quick stats (total devices, online counts, etc.)
- Detailed reports on meter readings
- Filtering by site, gateway, meter, date range
- Export capabilities (CSV)

## Business Rules and Validation

### Hierarchy Integrity
- Child entities must reference valid parent entities
- Deleting a parent should cascade appropriately (or prevent deletion)
- Site context filtering must respect all relationships

### Status Tracking
- Status is always computed, never stored
- 24-hour window is configurable but standard across the app
- Status should be displayed consistently (green = online, red = offline)

### Data Import Validation
- SQL files must contain required tables: meter_site, meter_details, user_tb
- CSV files must have valid headers and consistent column counts
- File size limits enforced (100MB)
- Jobs should be queued and processed asynchronously

### Code Uniqueness
- Entity codes should be unique within their scope
- Sites: globally unique codes
- Buildings: unique within site
- Locations: unique within site
- Gateways: globally unique (serial numbers)
- Meters: globally unique (serial numbers)

## Common Workflows

### Adding a New Meter
1. Ensure site, building, and location exist
2. Ensure gateway exists and is assigned to location
3. Create meter with required attributes
4. Assign to site, location, and gateway
5. Configure meter type and brand
6. Associate customer information if applicable

### Monitoring Device Status
1. Navigate to Gateways or Meters page
2. Use status filter to show only online or offline devices
3. Check last_log_update timestamp for details
4. Use site context filtering to focus on specific sites

### Importing Bulk Data
1. Navigate to Settings → Data Import
2. Select file (SQL or CSV)
3. System validates file structure
4. Submit for background processing
5. Monitor progress in Import History
6. Cancel if needed before completion

### Generating Reports
1. Navigate to Reports page
2. Select report type (meter readings, device status, etc.)
3. Apply filters (site, gateway, meter, date range)
4. Preview results
5. Export to CSV if needed
