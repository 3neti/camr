# CAMR Architecture

This document describes the architectural patterns and conventions used in the CAMR application.

## Application Architecture

CAMR uses a modern Laravel + Inertia.js + Vue 3 stack with a focus on:
- Server-side rendering with Inertia.js (no separate API)
- Type-safe routing with Laravel Wayfinder
- Component-based frontend with Vue 3 Composition API
- Test-driven development with Pest v4

## Backend Architecture

### Directory Structure

```
app/
├── Actions/              # Fortify authentication actions
│   └── Fortify/
├── Http/
│   ├── Controllers/      # Route controllers
│   │   ├── Settings/    # Settings controllers
│   │   └── Api/         # API controllers (if any)
│   ├── Middleware/       # Custom middleware
│   └── Requests/         # Form validation requests
├── Models/               # Eloquent models
└── Jobs/                # Queued jobs
```

### Controllers

Controllers are organized by entity or feature and follow these conventions:

**Location:** `app/Http/Controllers/`

**Naming:** `{Entity}Controller.php` (e.g., `SiteController`, `GatewayController`)

**Methods:** Follow RESTful conventions
- `index()` - List all resources
- `create()` - Show create form (rare with Inertia)
- `store()` - Create new resource
- `show()` - Show single resource
- `edit()` - Show edit form (rare with Inertia)
- `update()` - Update resource
- `destroy()` - Delete resource

**Example:**
```php
<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $sites = Site::query()
            ->when($request->search, fn($q, $search) => 
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
            )
            ->when(session('selected_site_id'), fn($q, $siteId) =>
                $q->where('id', $siteId)
            )
            ->paginate(25);
        
        return Inertia::render('sites/Index', [
            'sites' => $sites,
            'filters' => $request->only(['search']),
            'selectedSiteId' => session('selected_site_id'),
        ]);
    }
    
    public function store(SiteRequest $request)
    {
        $site = Site::create($request->validated());
        
        return redirect()->route('sites.index')
            ->with('success', 'Site created successfully.');
    }
}
```

**Settings Controllers:** Organized in `app/Http/Controllers/Settings/` subdirectory
- `ProfileController` - User profile management
- `DataImportController` - Data import functionality
- `PreferencesController` - User preferences

**API Controllers:** If needed, organized in `app/Http/Controllers/Api/` subdirectory

### Models

Models are located in `app/Models/` and follow these conventions:

**Relationships:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gateway extends Model
{
    // Belongs to relationships
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
    
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
    
    // Has many relationships
    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }
}
```

**Attributes (Computed Properties):**
```php
<?php

use Illuminate\Database\Eloquent\Casts\Attribute;

protected function status(): Attribute
{
    return Attribute::make(
        get: fn () => $this->last_log_update && 
            $this->last_log_update >= now()->subDay()
    );
}
```

**Casts:**
```php
<?php

protected function casts(): array
{
    return [
        'last_log_update' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
```

**Fillable:**
```php
<?php

protected $fillable = [
    'code',
    'name',
    'site_id',
    'location_id',
    'last_log_update',
];
```

### Form Requests

Form validation is handled by Form Request classes in `app/Http/Requests/`.

**Naming:** `{Entity}{Action}Request.php` (e.g., `SiteStoreRequest`, `GatewayUpdateRequest`)

**Example:**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or use Gate/Policy
    }
    
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:sites,code'],
            'name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'code.required' => 'Site code is required.',
            'code.unique' => 'This site code already exists.',
        ];
    }
}
```

### Routes

Routes are defined in `routes/` directory:

**Main Routes:** `routes/web.php`
```php
<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('sites', SiteController::class);
    Route::resource('buildings', BuildingController::class);
    Route::resource('locations', LocationController::class);
    Route::resource('gateways', GatewayController::class);
    Route::resource('meters', MeterController::class);
});

require __DIR__.'/settings.php';
```

**Settings Routes:** `routes/settings.php`
```php
<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\DataImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    
    Route::get('/data-import', [DataImportController::class, 'index'])->name('data-import.index');
    Route::post('/data-import', [DataImportController::class, 'store'])->name('data-import.store');
});
```

### Eloquent Query Patterns

**Eager Loading:** Always eager load relationships to prevent N+1 queries
```php
$gateways = Gateway::with(['site', 'location', 'meters'])->get();
```

**Query Scopes:** Use query scopes for reusable filters
```php
// In Gateway model
public function scopeOnline($query)
{
    return $query->whereNotNull('last_log_update')
        ->where('last_log_update', '>=', now()->subDay());
}

// Usage
$onlineGateways = Gateway::online()->get();
```

**Filtering with when():**
```php
$sites = Site::query()
    ->when($search, fn($q, $s) => $q->where('code', 'like', "%{$s}%"))
    ->when($siteId, fn($q, $id) => $q->where('site_id', $id))
    ->get();
```

### Jobs and Queues

Background jobs are located in `app/Jobs/`.

**Queue Configuration:** Uses `database` connection by default (see `config/queue.php`)

**Example Job:**
```php
<?php

namespace App\Jobs;

use App\Models\DataImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDataImport implements ShouldQueue
{
    use Queueable;
    
    public function __construct(
        public DataImport $import
    ) {}
    
    public function handle(): void
    {
        $this->import->update(['status' => 'processing']);
        
        try {
            // Process import logic here
            
            $this->import->update(['status' => 'completed']);
        } catch (\Exception $e) {
            $this->import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}
```

**Dispatching Jobs:**
```php
ProcessDataImport::dispatch($import);

// With delay
ProcessDataImport::dispatch($import)->delay(now()->addMinutes(5));

// On specific queue
ProcessDataImport::dispatch($import)->onQueue('imports');
```

## Frontend Architecture

### Directory Structure

```
resources/js/
├── actions/          # Wayfinder generated (gitignored)
├── components/       # Custom Vue components
│   ├── ui/          # shadcn-vue components
│   ├── AppHeader.vue
│   ├── AppSidebar.vue
│   └── ...
├── composables/     # Vue composables
│   └── useAppearance.ts
├── config/          # Frontend config
│   └── tableColumns.ts
├── layouts/         # Page layouts
│   ├── AppLayout.vue
│   ├── AuthLayout.vue
│   └── ...
├── pages/           # Inertia page components
│   ├── Dashboard.vue
│   ├── sites/
│   ├── buildings/
│   └── ...
├── routes/          # Wayfinder generated (gitignored)
├── types/           # TypeScript types
│   └── index.d.ts
├── app.ts           # App entry point
└── ssr.ts           # SSR entry point
```

### Page Component Structure

```vue
<script setup lang="ts">
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/ui/button'
import type { Site } from '@/types'

// Props from backend
interface Props {
  sites: {
    data: Site[]
    current_page: number
    per_page: number
    total: number
  }
  filters: {
    search?: string
  }
}

const props = defineProps<Props>()

// Computed properties
const totalPages = computed(() => Math.ceil(props.sites.total / props.sites.per_page))
</script>

<template>
  <AppLayout>
    <template #header>
      <h1>Sites</h1>
    </template>
    
    <!-- Page content -->
  </AppLayout>
</template>
```

### Inertia Patterns

**Rendering from Controller:**
```php
return Inertia::render('sites/Index', [
    'sites' => $sites,
    'filters' => $request->only(['search']),
]);
```

**Navigation in Vue:**
```vue
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { sites } from '@/routes'

const navigate = () => {
  router.visit(sites.index().url)
}
</script>
```

**Form Submission:**
```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { sites } from '@/routes'
</script>

<template>
  <Form v-bind="sites.store.form()" #default="{ errors, processing }">
    <input name="code" />
    <div v-if="errors.code">{{ errors.code }}</div>
    <button type="submit" :disabled="processing">Submit</button>
  </Form>
</template>
```

## Database Architecture

### Migrations

Migrations are located in `database/migrations/`.

**Naming:** `YYYY_MM_DD_HHMMSS_create_table_name_table.php`

**Example Migration:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_log_update')->nullable();
            $table->timestamps();
            
            $table->index(['site_id', 'last_log_update']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
```

### Factories

Factories are located in `database/factories/`.

**Example Factory:**
```php
<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class GatewayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'GW-' . $this->faker->unique()->numerify('######'),
            'name' => $this->faker->words(2, true),
            'site_id' => Site::factory(),
            'last_log_update' => $this->faker->dateTimeBetween('-2 days', 'now'),
        ];
    }
    
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_log_update' => now()->subHours(rand(1, 23)),
        ]);
    }
    
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_log_update' => now()->subDays(rand(2, 30)),
        ]);
    }
}
```

## Configuration

### Environment Variables

Configuration should NEVER use `env()` directly in code. Always use config files.

**Bad:**
```php
$timezone = env('APP_TIMEZONE');
```

**Good:**
```php
// config/app.php
'timezone' => env('APP_TIMEZONE', 'UTC'),

// In code
$timezone = config('app.timezone');
```

### Config Files

Located in `config/` directory:
- `app.php` - Application settings
- `database.php` - Database connections
- `queue.php` - Queue configuration
- `fortify.php` - Authentication features

## Authentication & Authorization

### Authentication

Powered by Laravel Fortify:
- Login/Register/Password Reset
- Two-Factor Authentication
- Email Verification
- Profile Management

### Authorization

Use Gates and Policies:

**Policy:**
```php
<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function update(User $user, Site $site): bool
    {
        return $user->isAdmin() || $user->canManageSite($site);
    }
}
```

**Usage in Controller:**
```php
$this->authorize('update', $site);
```

**Usage in Blade/Vue:**
```php
@can('update', $site)
    <!-- Show edit button -->
@endcan
```

## Best Practices

1. **Keep Controllers Thin** - Move business logic to Actions or Services
2. **Use Form Requests** - Always validate with Form Request classes
3. **Eager Load Relationships** - Prevent N+1 queries
4. **Use Query Scopes** - For reusable query logic
5. **Type Everything** - Use TypeScript for frontend, PHP type hints for backend
6. **Test Everything** - Write tests for all features
7. **Follow Conventions** - Stick to established patterns in the codebase
