<?php

use App\Models\DataImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use function Pest\Laravel\{actingAs, assertDatabaseCount, assertDatabaseHas, delete, get, post};

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    $this->adminUser = User::factory()->create(['role' => 'admin']);
    $this->regularUser = User::factory()->create(['role' => 'user']);
});

// Authorization Tests
test('non-admin users cannot access data import page', function () {
    actingAs($this->regularUser)
        ->get('/admin/data-import')
        ->assertForbidden();
});

test('guests cannot access data import page', function () {
    get('/admin/data-import')
        ->assertRedirect(route('login'));
});

test('admin users can access data import page', function () {
    actingAs($this->adminUser)
        ->get('/admin/data-import')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/DataImport'));
});

// Index/Show Tests
test('data import page shows user imports paginated', function () {
    DataImport::factory(15)->create(['user_id' => $this->adminUser->id]);
    DataImport::factory(5)->create(['user_id' => $this->regularUser->id]);

    actingAs($this->adminUser)
        ->get('/admin/data-import')
        ->assertInertia(fn ($page) => $page
            ->has('imports.data', 10)
            ->where('imports.total', 15)
        );
});

test('data import page shows only user owned imports', function () {
    DataImport::factory(3)->create(['user_id' => $this->adminUser->id]);
    DataImport::factory(5)->create(['user_id' => $this->regularUser->id]);

    actingAs($this->adminUser)
        ->get('/admin/data-import')
        ->assertInertia(fn ($page) => $page->where('imports.total', 3));
});

test('admin imports are ordered by most recent first', function () {
    $import1 = DataImport::factory()->create(['user_id' => $this->adminUser->id, 'created_at' => now()->subDay()]);
    $import2 = DataImport::factory()->create(['user_id' => $this->adminUser->id, 'created_at' => now()]);

    actingAs($this->adminUser)
        ->get('/admin/data-import')
        ->assertInertia(fn ($page) => $page
            ->where('imports.data.0.id', $import2->id)
            ->where('imports.data.1.id', $import1->id)
        );
});

// Upload Tests
test('admin can upload sql dump file', function () {
    $file = UploadedFile::fake()->create('test.sql', 100);

    actingAs($this->adminUser)
        ->post('/admin/data-import/upload', ['file' => $file])
        ->assertJson(['success' => true]);

    assertDatabaseCount('data_imports', 1);
    assertDatabaseHas('data_imports', [
        'user_id' => $this->adminUser->id,
        'filename' => 'test.sql',
        'status' => 'queued',
    ]);
});

test('non-admin cannot upload sql dump file', function () {
    $file = UploadedFile::fake()->create('test.sql', 100);

    actingAs($this->regularUser)
        ->post('/admin/data-import/upload', ['file' => $file])
        ->assertForbidden();

    assertDatabaseCount('data_imports', 0);
});

test('upload requires file parameter', function () {
    actingAs($this->adminUser)
        ->post('/admin/data-import/upload', [])
        ->assertSessionHasErrors('file');
});

test('upload requires sql file type', function () {
    $file = UploadedFile::fake()->create('test.txt', 100);

    actingAs($this->adminUser)
        ->post('/admin/data-import/upload', ['file' => $file])
        ->assertSessionHasErrors('file');
});

test('upload rejects files over 50mb', function () {
    $file = UploadedFile::fake()->create('test.sql', 51 * 1024); // 51 MB

    actingAs($this->adminUser)
        ->post('/admin/data-import/upload', ['file' => $file])
        ->assertSessionHasErrors('file');
});

test('upload stores file in storage/imports', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('meter_data.sql', 100);

    actingAs($this->adminUser)
        ->post('/admin/data-import/upload', ['file' => $file]);

    Storage::disk('local')->assertExists('imports/meter_data.sql');
});

// Status Tests
test('admin can check import status', function () {
    $import = DataImport::factory()->create([
        'user_id' => $this->adminUser->id,
        'status' => 'processing',
        'progress' => ['current' => 50, 'total' => 100],
        'statistics' => ['meters' => 10, 'meter_data' => 500],
    ]);

    actingAs($this->adminUser)
        ->get("/admin/data-import/{$import->id}/status")
        ->assertJson([
            'id' => $import->id,
            'status' => 'processing',
            'progress' => ['current' => 50, 'total' => 100],
            'statistics' => ['meters' => 10, 'meter_data' => 500],
        ]);
});

test('non-owner cannot check import status', function () {
    $import = DataImport::factory()->create(['user_id' => $this->regularUser->id]);

    actingAs($this->adminUser)
        ->get("/admin/data-import/{$import->id}/status")
        ->assertForbidden();
});

test('status endpoint returns json with formatted dates', function () {
    $import = DataImport::factory()->create([
        'user_id' => $this->adminUser->id,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $response = actingAs($this->adminUser)
        ->get("/admin/data-import/{$import->id}/status");

    $response->assertOk()
        ->assertJsonStructure([
            'id',
            'filename',
            'status',
            'progress',
            'statistics',
            'error_message',
            'started_at',
            'completed_at',
            'progress_percentage',
            'duration',
        ]);
});

// Cancel Tests
test('admin can cancel queued import', function () {
    $import = DataImport::factory()->create([
        'user_id' => $this->adminUser->id,
        'status' => 'queued',
    ]);

    actingAs($this->adminUser)
        ->delete("/admin/data-import/{$import->id}")
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('data_imports', [
        'id' => $import->id,
        'status' => 'cancelled',
    ]);
});

test('admin can cancel processing import', function () {
    $import = DataImport::factory()->create([
        'user_id' => $this->adminUser->id,
        'status' => 'processing',
    ]);

    actingAs($this->adminUser)
        ->delete("/admin/data-import/{$import->id}")
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('data_imports', [
        'id' => $import->id,
        'status' => 'cancelled',
    ]);
});

test('admin cannot cancel completed import', function () {
    $import = DataImport::factory()->create([
        'user_id' => $this->adminUser->id,
        'status' => 'completed',
    ]);

    actingAs($this->adminUser)
        ->delete("/admin/data-import/{$import->id}")
        ->assertStatus(422);

    $this->assertDatabaseHas('data_imports', [
        'id' => $import->id,
        'status' => 'completed',
    ]);
});

test('admin cannot cancel failed import', function () {
    $import = DataImport::factory()->create([
        'user_id' => $this->adminUser->id,
        'status' => 'failed',
    ]);

    actingAs($this->adminUser)
        ->delete("/admin/data-import/{$import->id}")
        ->assertStatus(422);

    $this->assertDatabaseHas('data_imports', [
        'id' => $import->id,
        'status' => 'failed',
    ]);
});

test('non-owner cannot cancel import', function () {
    $import = DataImport::factory()->create([
        'user_id' => $this->regularUser->id,
        'status' => 'queued',
    ]);

    actingAs($this->adminUser)
        ->delete("/admin/data-import/{$import->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('data_imports', [
        'id' => $import->id,
        'status' => 'queued',
    ]);
});

test('non-admin cannot cancel import', function () {
    $import = DataImport::factory()->create([
        'user_id' => $this->regularUser->id,
        'status' => 'queued',
    ]);

    actingAs($this->regularUser)
        ->delete("/admin/data-import/{$import->id}")
        ->assertForbidden();
});
