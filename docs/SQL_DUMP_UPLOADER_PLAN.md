# SQL Dump Data Import Uploader - Implementation Plan

## Overview
Replace the hardcoded LiveDataSeeder with a web-based uploader for SQL dump files. This allows production data to be imported without redeploying or modifying environment variables.

---

## Architecture

### Components

#### 1. Backend Controller
**File**: `app/Http/Controllers/Admin/DataImportController.php`

**Methods**:
- `show()` - Display upload form
- `upload(Request $request)` - Handle file upload
- `process()` - Background job to parse and import
- `status($jobId)` - Check import progress

**Responsibilities**:
- Validate uploaded SQL file (size, extension, basic integrity)
- Store file temporarily in `storage/app/imports/`
- Queue background job for processing
- Track import progress (total records, imported, errors)

#### 2. SQL Dump Parser
**File**: `app/Services/SqlDumpImporter.php`

**Methods**:
- `import(string $filePath): ImportResult`
- `parseFile(): void`
- `importSites(): int`
- `importUsers(): int`
- `importGateways(): int`
- `importMeters(): int`
- `importMeterData(): int`

**Features**:
- Reuse existing `SqlDumpParser` logic
- Transaction-based import (all or nothing)
- Progress tracking via events
- Detailed error logging

#### 3. Background Job
**File**: `app/Jobs/ImportSqlDumpJob.php`

**Purpose**:
- Run import async to avoid timeout on large files
- Report progress to UI via WebSocket or polling
- Handle failures gracefully
- Clean up temporary files after completion

#### 4. Database Models
**File**: `app/Models/DataImport.php`

**Fields**:
```php
- id: bigint
- user_id: bigint
- filename: string
- file_path: string
- status: enum('uploading', 'queued', 'processing', 'completed', 'failed')
- progress: json (records: 100, imported: 50, errors: 0)
- error_message: text
- statistics: json (sites: 5, users: 10, gateways: 3, meters: 50, meter_data: 18000)
- started_at: timestamp
- completed_at: timestamp
- created_at: timestamp
```

#### 5. Frontend Page
**File**: `resources/js/pages/Admin/DataImport.vue`

**Features**:
- File upload form (drag & drop support)
- Progress bar during upload
- Real-time import progress
- Import history table
- Download error report
- Cancel import option

#### 6. Routes
**File**: `routes/web.php`

```php
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('data-import', [DataImportController::class, 'show'])->name('admin.data-import.show');
    Route::post('data-import/upload', [DataImportController::class, 'upload'])->name('admin.data-import.upload');
    Route::get('data-import/{import}/status', [DataImportController::class, 'status'])->name('admin.data-import.status');
    Route::delete('data-import/{import}', [DataImportController::class, 'cancel'])->name('admin.data-import.cancel');
});
```

---

## Implementation Steps

### Phase 1: Core Backend (Day 1)

1. **Create Migration**
   - `database/migrations/xxxx_create_data_imports_table.php`
   - Fields: filename, status, progress, error_message, statistics, timestamps

2. **Create Model**
   - `app/Models/DataImport.php`
   - Relationships to User
   - Status helpers (isCompleted(), isFailed(), etc.)

3. **Create Service**
   - `app/Services/SqlDumpImporter.php`
   - Extract logic from LiveDataSeeder
   - Add progress tracking
   - Add error handling

4. **Create Job**
   - `app/Jobs/ImportSqlDumpJob.php`
   - Queue the import
   - Update DataImport status
   - Report progress

5. **Update Seeder**
   - Remove SQL dump dependency ✅ (DONE)
   - Add simple dummy data

### Phase 2: Controller & API (Day 2)

1. **Create Controller**
   - `app/Http/Controllers/Admin/DataImportController.php`
   - File validation
   - Job dispatching
   - Progress querying

2. **Add Request Validation**
   - `app/Http/Requests/UploadSqlDumpRequest.php`
   - Max 50MB file
   - .sql extension
   - Basic file integrity check

3. **Create API Endpoints**
   - POST `/admin/data-import/upload` - Accept file
   - GET `/admin/data-import/{import}/status` - Poll progress
   - DELETE `/admin/data-import/{import}` - Cancel import

### Phase 3: Frontend UI (Day 3)

1. **Create Page Component**
   - `resources/js/pages/Admin/DataImport.vue`
   - Upload form with drag & drop
   - Progress bar
   - History table

2. **Add Route**
   - `routes/web.php`
   - Protect with auth + admin role

3. **Add Navigation**
   - Update Admin sidebar menu
   - Link to import page

### Phase 4: Testing & Polish (Day 4)

1. **Unit Tests**
   - Service import logic
   - Model validations
   - Job dispatching

2. **Integration Tests**
   - Upload endpoint
   - Status polling
   - Error scenarios

3. **UI Polish**
   - File validation feedback
   - Error display
   - Success message
   - Cleanup temporary files

---

## File Size Handling

| File Size | Approach |
|-----------|----------|
| < 10MB | Direct processing |
| 10-50MB | Queue with progress |
| > 50MB | Chunked processing |

Current: 7.7MB → Use queue for safety and progress tracking

---

## Progress Tracking

### UI Updates
- Poll `/admin/data-import/{import}/status` every 2 seconds
- Show progress: "Importing 50/18000 meter readings..."
- Display ETA based on records/second

### Error Handling
- Validation errors: Show before import
- Processing errors: Stop import, show error log
- Partial success: Allow review and retry

---

## File Cleanup

- Keep uploads for 30 days in `storage/app/imports/`
- Delete after successful import
- Manual delete available in UI

---

## Future Enhancements

1. **Scheduled Imports** - Upload file, schedule import time
2. **Data Mapping** - Map custom SQL schemas to CAMR schema
3. **Rollback** - Undo import if errors detected
4. **Delta Sync** - Only import new/changed records
5. **Email Notifications** - Notify on completion/error

---

## Testing Checklist

- [ ] Upload valid SQL dump
- [ ] Upload invalid file (wrong extension)
- [ ] Upload oversized file
- [ ] Check progress updates in real-time
- [ ] Verify all data imported correctly
- [ ] Check error handling on corrupted SQL
- [ ] Test concurrent uploads
- [ ] Verify file cleanup
- [ ] Test on slow network (upload duration)

---

## Deployment Notes

1. **Database**: Run migration to create `data_imports` table
2. **Queue**: Ensure queue is running (`php artisan queue:work`)
3. **Storage**: Ensure `storage/app/imports/` is writable
4. **Permissions**: Only admins can access import page
5. **Backups**: Remind users to backup before large imports

---

## Estimated Effort

- Backend: 4-6 hours
- Frontend: 3-4 hours
- Testing: 2-3 hours
- **Total: 9-13 hours**
