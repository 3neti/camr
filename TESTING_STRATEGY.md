# Data Type Safety & Testing Strategy

This document outlines how we prevent type-related breaking changes and ensure data consistency across the backend and frontend.

## Problem Statement
We've repeatedly encountered `TypeError: .toFixed is not a function` errors because:
1. Backend returns numeric fields as strings (database serialization issue)
2. No frontend validation of API response types
3. No tests verifying data contract between frontend/backend
4. Type safety only enforced at component level (too late)

## Solution Layers

### Layer 1: Backend Type Safety
**File**: `app/Models/MeterData.php`
- **Mechanism**: Eloquent `$casts` property
- **Benefit**: Forces type conversion at model instantiation
- **Enforcement**: All numeric fields must have explicit float/int/boolean casts

**File**: `app/Http/Resources/MeterDataResource.php`
- **Mechanism**: JsonResource transformation layer
- **Benefit**: Double-checks types before API response sent to frontend
- **Enforcement**: Explicit casting in toArray() method

### Layer 2: Frontend Type Safety
**File**: `resources/js/lib/schemas.ts`
- **Mechanism**: Zod schema validation for API responses
- **Benefit**: Catches type mismatches immediately when data arrives
- **Enforcement**: Runtime validation logs errors to console

**File**: Vue component usage
```typescript
// BAD - no validation
const data = response.data.meter_data[0]
const formatted = data.wh_total.toFixed(2) // Runtime error if string

// GOOD - with validation
const validated = parseAndValidate(MeterDataSchema, response.data, 'meter show page')
const formatted = (validated.wh_total || 0).toFixed(2) // Safe
```

### Layer 3: Test Coverage
**What to test**:
1. API responses return correct types (Feature tests)
2. Models correctly cast fields (Unit tests)
3. Resources properly transform data (Resource tests)
4. Frontend handles edge cases (Component tests)

**Example Feature Test**:
```php
test('meter show returns numeric fields as floats', function () {
    $meter = Meter::factory()->has(MeterData::factory()->count(1))->create();
    $response = $this->get("/api/meters/{$meter->id}");
    
    $data = $response->json('data.meter_data.0');
    $this->assertIsFloat($data['wh_total']);
    $this->assertIsFloat($data['watt']);
});
```

## Checklist for New Features

When adding new numeric fields to MeterData:

- [ ] Add field to `app/Models/MeterData.php` `$fillable` array
- [ ] Add appropriate cast in `app/Models/MeterData.php` `$casts` array
- [ ] Add field to `app/Http/Resources/MeterDataResource.php` with explicit cast
- [ ] Update `resources/js/lib/schemas.ts` schema to include field
- [ ] Create feature test verifying field type in API response
- [ ] Use `parseAndValidate()` when consuming in Vue component

## Prevention Rules

1. **Never directly serialize model to JSON without Resource**
   - ❌ `return Inertia::render('Show', ['meter' => $meter])`
   - ✅ `return Inertia::render('Show', ['meter' => new MeterResource($meter)])`

2. **Always validate API responses in components**
   - ❌ `const value = apiResponse.value`
   - ✅ `const value = parseAndValidate(Schema, apiResponse, 'context')`

3. **Cast everything at the model level**
   - ✅ `protected $casts = ['watt' => 'float']`
   - ✅ `'watt' => $this->watt !== null ? (float) $this->watt : null,` in Resource

4. **Use TypeScript for frontend data structures**
   - Define explicit types for all API responses
   - Use `z.infer<>` to keep types DRY

## Useful Commands

```bash
# Run all tests
composer test

# Run specific test
php artisan test --filter=MeterDataResourceTest

# Check TypeScript types
npm run typecheck

# Lint frontend for type issues
npm run lint
```

## References
- [Zod Documentation](https://zod.dev)
- [Laravel Resources](https://laravel.com/docs/resources)
- [Eloquent Casting](https://laravel.com/docs/eloquent-mutators#attribute-casting)
