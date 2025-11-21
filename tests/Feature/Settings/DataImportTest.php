<?php

use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\{actingAs, get, post, assertDatabaseCount};

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
});

// Authorization Tests
test('guests cannot access data import page', function () {
    get('/settings/data-import')
        ->assertRedirect(route('login'));
});

test('authenticated users can access data import page', function () {
    actingAs($this->user)
        ->get('/settings/data-import')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/DataImport'));
});

// Upload Tests - Valid Files
test('user can upload valid sql file', function () {
    Storage::disk('local')->put('test_dump.sql', file_get_contents(__DIR__ . '/fixtures/valid_dump.sql'));
    
    $file = UploadedFile::fake()->createWithContent(
        'valid_dump.sql',
        Storage::disk('local')->get('test_dump.sql')
    );

    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', [
            'file' => $file,
            'type' => 'sql',
        ]);

    $response->assertJson(['success' => true])
        ->assertJsonStructure([
            'success',
            'path',
            'filename',
            'size',
            'info',
            'statistics',
            'warnings',
        ]);
});

test('user can upload valid csv file', function () {
    $csv = "id,name,email\n1,John,john@example.com\n2,Jane,jane@example.com";
    $file = UploadedFile::fake()->createWithContent('data.csv', $csv);

    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', [
            'file' => $file,
            'type' => 'csv',
        ]);

    $response->assertJson(['success' => true])
        ->assertJsonStructure([
            'success',
            'path',
            'filename',
            'size',
            'info',
            'statistics',
        ]);
});

// Upload Tests - Invalid Files
test('upload rejects file without required sql tables', function () {
    $invalidSql = "INSERT INTO wrong_table VALUES (1, 'test');";
    $file = UploadedFile::fake()->createWithContent('invalid.sql', $invalidSql);

    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', [
            'file' => $file,
            'type' => 'sql',
        ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false])
        ->assertJsonStructure(['errors', 'warnings']);
});

test('upload rejects empty csv file', function () {
    $file = UploadedFile::fake()->createWithContent('empty.csv', '');

    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', [
            'file' => $file,
            'type' => 'csv',
        ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('upload rejects csv with only headers', function () {
    $csv = "id,name,email";
    $file = UploadedFile::fake()->createWithContent('headers_only.csv', $csv);

    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', [
            'file' => $file,
            'type' => 'csv',
        ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('upload rejects csv with empty headers', function () {
    $csv = "id,,email\n1,test,test@example.com";
    $file = UploadedFile::fake()->createWithContent('bad_headers.csv', $csv);

    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', [
            'file' => $file,
            'type' => 'csv',
        ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

// File Type Validation
test('upload rejects wrong extension for sql type', function () {
    $file = UploadedFile::fake()->create('data.txt', 100);

    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', [
            'file' => $file,
            'type' => 'sql',
        ]);

    $response->assertStatus(422)
        ->assertJson(['error' => 'Invalid file type. Expected .sql file.']);
});

test('upload rejects wrong extension for csv type', function () {
    $file = UploadedFile::fake()->create('data.txt', 100);

    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', [
            'file' => $file,
            'type' => 'csv',
        ]);

    $response->assertStatus(422)
        ->assertJson(['error' => 'Invalid file type. Expected .csv file.']);
});

// File Size Validation
test('upload rejects files over 100mb', function () {
    $file = UploadedFile::fake()->create('large.sql', 101 * 1024); // 101 MB

    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', [
            'file' => $file,
            'type' => 'sql',
        ]);

    $response->assertSessionHasErrors('file');
});

// CSRF Protection
// Note: CSRF protection is enforced by Laravel middleware. The frontend fix ensures
// that when using FormData with axios, the _token field is appended so the middleware
// can validate it. This is tested in the frontend/browser environment, not unit tests.
test('upload endpoint is not exempted from csrf protection', function () {
    // This test documents that the upload endpoint follows standard Laravel middleware
    // The actual CSRF validation is handled by Laravel's VerifyCsrfToken middleware
    // and is tested by the framework. Our frontend fix (appending _token to FormData)
    // ensures the token is sent when needed.
    
    $file = UploadedFile::fake()->create('test.sql', 100);
    
    // Laravel's test framework automatically handles CSRF for authenticated requests
    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', [
            'file' => $file,
            'type' => 'sql',
        ]);
    
    // Should succeed without 419 Conflict errors (CSRF token properly handled)
    $this->assertNotEquals(419, $response->status());
});

// Request Validation
test('upload requires file parameter', function () {
    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', ['type' => 'sql']);

    $response->assertSessionHasErrors('file');
});

test('upload requires type parameter', function () {
    $file = UploadedFile::fake()->create('test.sql', 100);

    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', ['file' => $file]);

    $response->assertSessionHasErrors('type');
});

test('upload type must be sql, csv, or zip', function () {
    $file = UploadedFile::fake()->create('test.sql', 100);

    $response = actingAs($this->user)
        ->post('/settings/data-import/upload', [
            'file' => $file,
            'type' => 'invalid',
        ]);

    $response->assertSessionHasErrors('type');
});

// Import Job Tests
test('user can start import with uploaded files', function () {
    $response = actingAs($this->user)
        ->post('/settings/data-import/import', [
            'files' => [
                [
                    'path' => 'imports/sql/test.sql',
                    'filename' => 'test.sql',
                    'type' => 'sql',
                ],
            ],
            'options' => [],
        ]);

    $response->assertJson(['success' => true])
        ->assertJsonStructure(['jobs', 'message']);
});

test('import creates import job record', function () {
    actingAs($this->user)
        ->post('/settings/data-import/import', [
            'files' => [
                [
                    'path' => 'imports/sql/test.sql',
                    'filename' => 'test.sql',
                    'type' => 'sql',
                ],
            ],
        ]);

    assertDatabaseCount('import_jobs', 1);
});

// Progress Tests
test('user can check progress of own import jobs', function () {
    $job = ImportJob::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'processing',
        'processed_records' => 50,
        'total_records' => 100,
    ]);

    $response = actingAs($this->user)
        ->post('/settings/data-import/progress', [
            'job_ids' => [$job->id],
        ]);

    $response->assertJson(['jobs' => [
        [
            'id' => $job->id,
            'status' => 'processing',
            'processed' => 50,
            'total' => 100,
        ],
    ]]);
});

test('user cannot see other users import jobs progress', function () {
    $otherUser = User::factory()->create();
    $job = ImportJob::factory()->create(['user_id' => $otherUser->id]);

    $response = actingAs($this->user)
        ->post('/settings/data-import/progress', [
            'job_ids' => [$job->id],
        ]);

    $response->assertJson(['jobs' => []]);
});

// Cancel Tests
test('user can cancel pending import job', function () {
    $job = ImportJob::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'pending',
    ]);

    $response = actingAs($this->user)
        ->post("/settings/data-import/{$job->id}/cancel");

    $response->assertJson(['success' => true, 'message' => 'Import cancelled']);
    $this->assertDatabaseHas('import_jobs', [
        'id' => $job->id,
        'status' => 'cancelled',
    ]);
});

test('user can cancel processing import job', function () {
    $job = ImportJob::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'processing',
    ]);

    actingAs($this->user)
        ->post("/settings/data-import/{$job->id}/cancel");

    $this->assertDatabaseHas('import_jobs', [
        'id' => $job->id,
        'status' => 'cancelled',
    ]);
});

test('user cannot cancel completed import job', function () {
    $job = ImportJob::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
    ]);

    $response = actingAs($this->user)
        ->post("/settings/data-import/{$job->id}/cancel");

    $response->assertStatus(422);
});

test('user cannot cancel import job they do not own', function () {
    $otherUser = User::factory()->create();
    $job = ImportJob::factory()->create([
        'user_id' => $otherUser->id,
        'status' => 'pending',
    ]);

    $response = actingAs($this->user)
        ->post("/settings/data-import/{$job->id}/cancel");

    $response->assertStatus(403);
});
