# CAMR Testing Guidelines

This document describes testing conventions, patterns, and best practices for the CAMR application.

## Testing Framework

- **Pest v4** - Modern PHP testing framework with expressive syntax
- **Browser Testing** - Built-in browser testing capabilities in Pest v4
- **Feature Tests** - Primary test type for application features
- **Unit Tests** - For isolated component testing

## Test Organization

```
tests/
├── Feature/              # Feature tests (primary)
│   ├── SiteTest.php
│   ├── BuildingTest.php
│   ├── LocationTest.php
│   ├── GatewayTest.php
│   ├── MeterTest.php
│   ├── DataImportTest.php
│   └── ...
├── Unit/                 # Unit tests
│   └── ...
├── Browser/              # Browser tests (Pest v4)
│   └── ...
├── Pest.php             # Pest configuration
└── TestCase.php         # Base test case
```

## Running Tests

```bash
# Run all tests
composer test
# or
php artisan test

# Run specific test file
php artisan test tests/Feature/SiteTest.php

# Run specific test by name
php artisan test --filter=test_can_create_site
# or
php artisan test --filter="it creates a site"

# Run with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

## Test Structure

### Basic Pest Test

```php
<?php

use App\Models\Site;
use App\Models\User;

it('creates a site', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->post('/sites', [
            'code' => 'SITE-001',
            'name' => 'Test Site',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('sites', [
        'code' => 'SITE-001',
    ]);
});

it('requires code when creating site', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->post('/sites', [
            'name' => 'Test Site',
        ])
        ->assertSessionHasErrors('code');
});
```

### Using Test Datasets

For testing validation rules with multiple scenarios:

```php
<?php

it('validates site code format', function (string $code, bool $shouldPass) {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->post('/sites', [
            'code' => $code,
            'name' => 'Test Site',
        ]);
    
    if ($shouldPass) {
        $response->assertRedirect();
    } else {
        $response->assertSessionHasErrors('code');
    }
})->with([
    ['SITE-001', true],
    ['SITE001', true],
    ['', false],
    ['A', false],
    [str_repeat('A', 256), false],
]);
```

## Factory Usage

Always use factories to create test data:

```php
<?php

use App\Models\Site;
use App\Models\Building;
use App\Models\Gateway;
use App\Models\Meter;

// Basic factory usage
$site = Site::factory()->create();

// With custom attributes
$site = Site::factory()->create([
    'code' => 'CUSTOM-001',
]);

// Create multiple
$sites = Site::factory()->count(5)->create();

// With relationships
$building = Building::factory()
    ->for(Site::factory())
    ->create();

// Using states (if defined in factory)
$gateway = Gateway::factory()
    ->online()
    ->create();

$meter = Meter::factory()
    ->offline()
    ->create();
```

## Common Test Scenarios

### CRUD Operations

```php
<?php

use App\Models\Site;
use App\Models\User;

describe('Site CRUD', function () {
    it('lists sites', function () {
        $sites = Site::factory()->count(3)->create();
        
        $this->actingAs(User::factory()->create())
            ->get('/sites')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('sites/Index')
                ->has('sites.data', 3)
            );
    });
    
    it('shows a site', function () {
        $site = Site::factory()->create();
        
        $this->actingAs(User::factory()->create())
            ->get("/sites/{$site->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('sites/Show')
                ->where('site.id', $site->id)
            );
    });
    
    it('creates a site', function () {
        $this->actingAs(User::factory()->create())
            ->post('/sites', [
                'code' => 'SITE-001',
                'name' => 'Test Site',
            ])
            ->assertRedirect();
        
        $this->assertDatabaseHas('sites', [
            'code' => 'SITE-001',
        ]);
    });
    
    it('updates a site', function () {
        $site = Site::factory()->create();
        
        $this->actingAs(User::factory()->create())
            ->put("/sites/{$site->id}", [
                'code' => $site->code,
                'name' => 'Updated Name',
            ])
            ->assertRedirect();
        
        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'name' => 'Updated Name',
        ]);
    });
    
    it('deletes a site', function () {
        $site = Site::factory()->create();
        
        $this->actingAs(User::factory()->create())
            ->delete("/sites/{$site->id}")
            ->assertRedirect();
        
        $this->assertDatabaseMissing('sites', [
            'id' => $site->id,
        ]);
    });
});
```

### Site Context Filtering

```php
<?php

use App\Models\Site;
use App\Models\Building;
use App\Models\User;

it('filters buildings by selected site', function () {
    $site1 = Site::factory()->create();
    $site2 = Site::factory()->create();
    
    $building1 = Building::factory()->for($site1)->create();
    $building2 = Building::factory()->for($site2)->create();
    
    // Select site and navigate to buildings
    $this->actingAs(User::factory()->create())
        ->get("/buildings?site_id={$site1->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('buildings/Index')
            ->has('buildings.data', 1)
            ->where('buildings.data.0.id', $building1->id)
        );
});

it('persists site selection in session', function () {
    $site = Site::factory()->create();
    
    $this->actingAs(User::factory()->create())
        ->get("/sites/{$site->id}/select")
        ->assertSessionHas('selected_site_id', $site->id);
});

it('clears site filter when all sites selected', function () {
    $site = Site::factory()->create();
    
    $this->actingAs(User::factory()->create())
        ->withSession(['selected_site_id' => $site->id])
        ->get('/buildings')
        ->assertSessionMissing('selected_site_id');
});
```

### Status Tracking

```php
<?php

use App\Models\Gateway;

it('marks gateway as online when recently updated', function () {
    $gateway = Gateway::factory()->create([
        'last_log_update' => now()->subHours(12),
    ]);
    
    expect($gateway->status)->toBeTrue();
});

it('marks gateway as offline when not updated recently', function () {
    $gateway = Gateway::factory()->create([
        'last_log_update' => now()->subDays(2),
    ]);
    
    expect($gateway->status)->toBeFalse();
});

it('filters gateways by online status', function () {
    $onlineGateway = Gateway::factory()->create([
        'last_log_update' => now()->subHours(1),
    ]);
    
    $offlineGateway = Gateway::factory()->create([
        'last_log_update' => now()->subDays(2),
    ]);
    
    $this->actingAs(User::factory()->create())
        ->get('/gateways?status=online')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('gateways.data', 1)
            ->where('gateways.data.0.id', $onlineGateway->id)
        );
});
```

### Data Import Testing

```php
<?php

use App\Models\DataImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('validates SQL file upload', function () {
    Storage::fake('local');
    
    $file = UploadedFile::fake()->create('data.sql', 1024, 'application/sql');
    
    $this->actingAs(User::factory()->create())
        ->post('/settings/data-import', [
            'file' => $file,
            'file_type' => 'sql',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('data_imports', [
        'file_type' => 'sql',
        'status' => 'pending',
    ]);
});

it('rejects files over size limit', function () {
    Storage::fake('local');
    
    $file = UploadedFile::fake()->create('large.sql', 101 * 1024, 'application/sql');
    
    $this->actingAs(User::factory()->create())
        ->post('/settings/data-import', [
            'file' => $file,
            'file_type' => 'sql',
        ])
        ->assertSessionHasErrors('file');
});

it('cancels pending import', function () {
    $import = DataImport::factory()->create([
        'status' => 'pending',
    ]);
    
    $this->actingAs(User::factory()->create())
        ->delete("/settings/data-import/{$import->id}")
        ->assertRedirect();
    
    $this->assertDatabaseHas('data_imports', [
        'id' => $import->id,
        'status' => 'cancelled',
    ]);
});
```

### Authentication and Authorization

```php
<?php

use App\Models\User;

it('requires authentication to access sites', function () {
    $this->get('/sites')
        ->assertRedirect('/login');
});

it('allows authenticated users to access sites', function () {
    $this->actingAs(User::factory()->create())
        ->get('/sites')
        ->assertOk();
});

it('prevents unauthorized access to admin features', function () {
    $user = User::factory()->create(); // Non-admin user
    
    $this->actingAs($user)
        ->get('/admin/settings')
        ->assertForbidden();
});
```

## Browser Testing (Pest v4)

Use browser tests for complex user interactions and JavaScript-heavy features:

```php
<?php

use App\Models\User;

it('allows user to select site and filter buildings', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    $page = visit('/sites');
    
    $page->assertSee('Sites')
        ->assertNoJavascriptErrors()
        ->click('[data-testid="site-row-1"]')
        ->click('[data-testid="view-buildings"]')
        ->assertSee('Buildings')
        ->assertSee('Filtered by site');
});

it('validates form submission with errors', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    $page = visit('/sites/create');
    
    $page->assertSee('Create Site')
        ->click('button[type="submit"]')
        ->assertSee('The code field is required');
});

it('supports dark mode', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    $page = visit('/dashboard');
    
    $page->assertNoJavascriptErrors()
        ->click('[data-testid="theme-toggle"]')
        ->assertAttribute('html', 'class', 'dark');
});
```

## Testing with Queues and Events

```php
<?php

use App\Jobs\ProcessDataImport;
use App\Models\DataImport;
use Illuminate\Support\Facades\Queue;

it('dispatches import job when file uploaded', function () {
    Queue::fake();
    
    $import = DataImport::factory()->create();
    
    ProcessDataImport::dispatch($import);
    
    Queue::assertPushed(ProcessDataImport::class, function ($job) use ($import) {
        return $job->import->id === $import->id;
    });
});
```

## Testing Best Practices

### 1. Use Descriptive Test Names

```php
// Good
it('prevents deleting site with associated buildings', function () { ... });

// Bad
it('test site delete', function () { ... });
```

### 2. Arrange-Act-Assert Pattern

```php
it('updates gateway status', function () {
    // Arrange
    $gateway = Gateway::factory()->create();
    
    // Act
    $gateway->update(['last_log_update' => now()]);
    
    // Assert
    expect($gateway->fresh()->status)->toBeTrue();
});
```

### 3. Test Both Happy and Failure Paths

```php
describe('Site creation', function () {
    it('creates site with valid data', function () { ... });
    
    it('fails when code is missing', function () { ... });
    
    it('fails when code already exists', function () { ... });
    
    it('fails when code exceeds max length', function () { ... });
});
```

### 4. Keep Tests Isolated

```php
// Good - each test creates its own data
it('creates site', function () {
    $site = Site::factory()->create();
    // test logic
});

// Bad - relying on data from previous tests
it('uses existing site', function () {
    $site = Site::first(); // Don't do this
    // test logic
});
```

### 5. Use Appropriate Assertions

```php
// Database assertions
$this->assertDatabaseHas('sites', ['code' => 'SITE-001']);
$this->assertDatabaseMissing('sites', ['code' => 'DELETED']);

// Response assertions
$response->assertOk();
$response->assertRedirect();
$response->assertSessionHas('success');
$response->assertSessionHasErrors('code');

// Pest expectations
expect($gateway->status)->toBeTrue();
expect($sites)->toHaveCount(5);
expect($site->code)->toBe('SITE-001');
```

## RefreshDatabase

Always use `RefreshDatabase` trait in tests that interact with the database:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('tests something with database', function () {
    // Database is fresh for this test
});
```

This is typically configured in `tests/Pest.php` globally:

```php
uses(
    Tests\TestCase::class,
    RefreshDatabase::class,
)->in('Feature');
```
