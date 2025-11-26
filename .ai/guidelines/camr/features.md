# CAMR Features

This document details the key features and functionality of the CAMR application.

## Data Import System

The Data Import feature allows administrators to bulk import meter data and related records from external sources.

### Access
- Navigate to Settings → Data Import
- Available to users with appropriate permissions

### Supported File Formats

#### SQL Files (.sql)
- Maximum file size: 100MB
- Must contain INSERT statements for required tables:
  - `meter_site` — Site information
  - `meter_details` — Meter specifications and readings
  - `user_tb` — Associated user data
- File is validated for:
  - Correct extension (.sql)
  - File size within limits
  - Presence of required table INSERT statements
  - Valid SQL syntax structure

#### CSV Files (.csv)
- Maximum file size: 100MB
- Requirements:
  - First row must contain non-empty header names
  - At least one data row required
  - All rows must have the same number of columns as header
- File is validated for:
  - Correct extension (.csv)
  - File size within limits
  - Valid header structure
  - Row consistency

### Import Process

1. **File Upload**
   - Drag-and-drop or click to select file
   - Choose file type (SQL or CSV)
   - System performs initial validation

2. **Validation**
   - Extension check (must match selected type)
   - Size check (max 100MB)
   - Content validation (structure, headers, required tables)
   - Warnings shown for potential issues

3. **Processing**
   - Job created with status "Pending"
   - Added to queue for background processing
   - Status updates: Pending → Processing → Completed/Failed
   - Real-time progress tracking

4. **Monitoring**
   - Import History panel shows all jobs
   - Columns: File Name, Type, Status, Records (processed/total), Progress, Actions
   - Progress bar updates during processing
   - Cancellation available for Pending/Processing jobs

### Models and Database

#### DataImport Model
- Stores import job metadata
- Fields:
  - `file_name` — Original filename
  - `file_path` — Storage path
  - `file_type` — SQL or CSV
  - `status` — Pending, Processing, Completed, Failed
  - `processed_records` — Count of processed records
  - `total_records` — Total records to process
  - `error_message` — Failure details if applicable
  - `user_id` — User who initiated import

#### ImportJob Model
- Background job for processing imports
- Uses Laravel queues (database connection)
- Handles actual data parsing and insertion
- Updates DataImport status and progress
- Supports cancellation

### Business Rules

- Only authenticated users with proper permissions can import data
- SQL files must include all three required tables
- CSV header validation is strict (no empty headers)
- Row count validation ensures data integrity
- Failed imports preserve error messages for debugging
- Completed imports cannot be cancelled
- File storage is managed securely

### Error Handling

Common issues and solutions:
- **419 error (CSRF)**: Session expired, refresh and sign in again
- **Invalid file type**: Ensure extension matches selection
- **Missing required tables**: SQL must contain meter_site, meter_details, user_tb
- **Invalid CSV structure**: Check headers and row column counts
- **File too large**: Split into multiple files under 100MB

## Site Context Filtering

A powerful feature that allows filtering all related data by selecting a site.

### How It Works

1. **Site Selection**
   - On Sites page, click a row to select a site
   - Click building (🏢), location (📍), gateway (📡), or meter (⚡) icon
   - System stores selected site in session

2. **Filter Persistence**
   - Selected site persists across page navigation
   - All index pages automatically filter by selected site
   - Filter remains active until cleared or new site selected

3. **Filter Application**
   - Buildings: Shows only buildings in selected site
   - Locations: Shows only locations in selected site
   - Gateways: Shows only gateways in selected site
   - Meters: Shows only meters in selected site

4. **Clearing the Filter**
   - Select "All Sites" in any site dropdown filter
   - Filter is cleared from session
   - All pages return to showing all records

### Implementation Details

- Site ID stored in session: `session('selected_site_id')`
- Passed as Inertia prop to all relevant pages
- Applied to Eloquent queries: `->when($siteId, fn($q) => $q->where('site_id', $siteId))`
- Icon buttons on Sites page use route parameters to pass site context

### Business Rules

- Only one site can be selected at a time
- Filter applies globally across the app
- User can always clear filter by selecting "All Sites"
- Filter respects user permissions (can't see sites they don't have access to)

## Analytics and Dashboard

### Dashboard Features

The main dashboard provides an overview of system status:

- **Quick Stats Cards**
  - Total Sites
  - Total Gateways (with online/offline breakdown)
  - Total Meters (with online/offline breakdown)
  - Recent activity summary

- **Status Indicators**
  - Visual representation of online/offline devices
  - Color coding: Green (online), Red (offline)
  - Click-through to detailed views

- **Recent Activity**
  - Latest data imports
  - Recent gateway updates
  - New meters registered
  - Configuration changes

### Analytics Controller

Located at `app/Http/Controllers/AnalyticsController.php`, handles:
- Aggregating statistics
- Filtering by site context
- Date range queries
- Status calculations

### Reports

#### Available Reports
- **Device Status Report**: Current status of all gateways and meters
- **Meter Reading Report**: Historical meter readings with filters
- **Site Activity Report**: Activity summary per site
- **Configuration History**: Changes to gateway/meter configurations

#### Report Features
- Filter by site, gateway, meter, date range
- Sort by multiple columns
- Export to CSV
- Preview before export
- Pagination for large datasets

#### Report Generation Process
1. Navigate to Reports page
2. Select report type
3. Apply filters (site, date range, status, etc.)
4. Click "Generate Report"
5. Preview results in table format
6. Export to CSV if needed

## Configuration File Management

### Overview
Manage configuration files for gateways and meters, track versions, and maintain configuration history.

### Features

#### Upload Configuration
- Associate files with specific gateway or meter
- Store file metadata (name, size, upload date, user)
- Version tracking for configuration changes

#### View Configuration History
- List all configurations for a device
- Filter by date range
- Compare versions
- Download previous configurations

#### Download Configuration
- Download current or historical configurations
- Access control: only authorized users can download
- Audit trail of downloads

### ConfigurationFile Model

Key attributes:
- `file_name` — Original filename
- `file_path` — Storage location
- `file_size` — Size in bytes
- `file_type` — MIME type
- `configurable_type` — Gateway or Meter (polymorphic)
- `configurable_id` — ID of associated device
- `version` — Version number
- `uploaded_by` — User ID

### Business Rules

- Configuration files are stored securely in private storage
- Each upload creates a new version
- Previous versions are retained for history
- Only authorized users can upload/download configurations
- Audit trail maintained for all configuration changes

## Table Features

All index pages (Sites, Buildings, Locations, Gateways, Meters) support advanced table features:

### Column Customization

- **Show/Hide Columns**: Select which columns to display
- **Reorder Columns**: Drag to reorder column positions
- **Column Width**: Adjustable column widths
- **Column Alignment**: Left, center, or right alignment

Configuration stored in `resources/js/config/tableColumns.ts` (user-specific overrides in localStorage)

### Search and Filter

- **Quick Search**: Search by code or name
- **Column Filters**: Filter individual columns
- **Status Filters**: Filter by online/offline status
- **Site Filter**: Filter by selected site
- **Date Range Filters**: Filter by date ranges where applicable

### Filter Presets

- **Save Filter Combinations**: Save frequently used filter sets
- **Quick Apply**: One-click to apply saved filters
- **Manage Presets**: Edit or delete saved filter presets
- **Share Presets**: Share with team members (if enabled)

### Sorting

- **Single Column Sort**: Click header to sort by that column
- **Multi-Column Sort**: Shift+click for secondary sort
- **Sort Direction**: Toggle ascending/descending
- **Sort Persistence**: Sort preference saved in session

### Export

- **Export to CSV**: Export filtered/sorted data
- **Select Columns**: Choose which columns to include
- **Export All or Current Page**: Option to export all data or current page only
- **Background Processing**: Large exports processed in queue

### Pagination

- **Records Per Page**: Select 10, 25, 50, 100 records per page
- **Jump to Page**: Direct page navigation
- **Keyboard Navigation**: Arrow keys to navigate pages
- **Total Count**: Shows total matching records

## User Management

### Features

- **User List**: View all system users
- **Add User**: Create new user accounts
- **Edit User**: Update user information and permissions
- **Deactivate User**: Temporarily disable user access
- **Role Management**: Assign roles and permissions

### Two-Factor Authentication

- Powered by Laravel Fortify
- Users can enable/disable 2FA in profile settings
- QR code generation for authenticator apps
- Recovery codes provided
- Admin can require 2FA for all users

### Permissions

- Role-based access control
- Site-specific permissions (access to specific sites)
- Feature-based permissions (data import, configuration management, etc.)
- Admin, Manager, Operator, Viewer roles (or similar)

## Profile and Settings

### Profile Management

- Update name, email
- Change password
- Enable/disable two-factor authentication
- View login history
- Update notification preferences

### Application Settings

- **General Settings**: App name, timezone, locale
- **Data Import Settings**: Configure import rules and validation
- **Notification Settings**: Email, SMS, webhook configurations
- **Appearance**: Theme preferences (light/dark mode)
