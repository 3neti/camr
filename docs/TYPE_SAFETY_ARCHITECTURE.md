# Three-Layer Type Safety Architecture

## Overview

This document explains the comprehensive type safety system implemented to prevent runtime type errors (like `TypeError: .toFixed is not a function`) that occur when data flows between the Laravel backend and Vue.js frontend.

## The Problem We Solved

### Initial Issue
When fetching meter data from the API, numeric fields like `wh_total`, `wh_delivered`, and `watt` were being returned as strings instead of numbers:

```javascript
// BEFORE: Strings from database
const data = { wh_total: "189804.80", wh_delivered: "0.00" }
const formatted = data.wh_total / 1000  // = "189804.80" / 1000 = NaN
const result = formatted.toFixed(2)     // ERROR: NaN.toFixed is not a function
```

### Root Causes
1. **Database serialization** - SQLite stores all values as strings until explicitly cast
2. **No transformation layer** - Direct Eloquent model serialization doesn't guarantee types
3. **No frontend validation** - Frontend assumed correct types without checking
4. **No contract tests** - No tests verifying the API data contract
5. **Late-stage error discovery** - Errors only found in production via console

## Three-Layer Solution

```
┌─────────────────────────────────────────────────────────────────┐
│ Layer 3: Documentation & Process                                │
│ TESTING_STRATEGY.md - Prevents future regressions               │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │
┌─────────────────────────────────────────────────────────────────┐
│ Layer 2: Frontend Type Safety                                   │
│ schemas.ts - Runtime validation catches type mismatches         │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │
┌─────────────────────────────────────────────────────────────────┐
│ Layer 1: Backend Type Safety                                    │
│ MeterDataResource.php - Guarantees types before API response    │
└─────────────────────────────────────────────────────────────────┘
```

Each layer is independent but works together for defense-in-depth.

---

## LAYER 1: Backend Type Safety

### Purpose
Guarantee that all numeric fields are converted to the correct type **before leaving the backend**. This is the strongest defense because:
- Controlled environment (PHP type system)
- Single source of truth for data transformation
- Prevents bad data from ever being sent to frontend

### Implementation

#### 1.1 Model-Level Casting (`app/Models/MeterData.php`)

The first checkpoint: Eloquent casts ensure types when the model is instantiated.

```php
protected $casts = [
    // Voltage readings - stored as strings in DB, cast to float
    'vrms_a' => 'float',
    'vrms_b' => 'float',
    'vrms_c' => 'float',
    
    // Current readings
    'irms_a' => 'float',
    'irms_b' => 'float',
    'irms_c' => 'float',
    
    // Power values
    'watt' => 'float',
    'va' => 'float',
    'var' => 'float',
    
    // Energy values
    'wh_total' => 'float',
    'wh_delivered' => 'float',
    'wh_received' => 'float',
];
```

**How it works:**
```php
// Raw database record
$record = ['wh_total' => '189804.80', 'watt' => '42'];

// When loaded via Eloquent
$meterData = MeterData::find(1);
$meterData->wh_total;  // (float) 189804.80 - automatically cast!
$meterData->watt;      // (float) 42
```

**Why this alone isn't enough:**
- Eloquent casts work, but serialization (toArray, toJson) can revert to strings
- Inertia serialization bypasses casts in some cases
- Want explicit confirmation before API response

#### 1.2 Resource-Level Transformation (`app/Http/Resources/MeterDataResource.php`)

The second checkpoint: Explicit transformation layer that **cannot be bypassed**.

```php
class MeterDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Voltage - explicitly cast to float
            'vrms_a' => $this->vrms_a !== null ? (float) $this->vrms_a : null,
            'vrms_b' => $this->vrms_b !== null ? (float) $this->vrms_b : null,
            'vrms_c' => $this->vrms_c !== null ? (float) $this->vrms_c : null,
            
            // Current - explicitly cast to float
            'irms_a' => $this->irms_a !== null ? (float) $this->irms_a : null,
            'irms_b' => $this->irms_b !== null ? (float) $this->irms_b : null,
            'irms_c' => $this->irms_c !== null ? (float) $this->irms_c : null,
            
            // Energy values - explicitly cast to float
            'wh_total' => $this->wh_total !== null ? (float) $this->wh_total : null,
            'wh_delivered' => $this->wh_delivered !== null ? (float) $this->wh_delivered : null,
        ];
    }
}
```

**Why explicit casting in Resource?**
- Even if model casting fails or is bypassed, Resource ensures the type
- Null-safe: `value !== null ? (float) value : null` prevents float(null)
- Clear intent: Developers see exactly what type is guaranteed

#### 1.3 Controller Integration

```php
public function show(Meter $meter): Response
{
    $meter->load(['meterData' => fn($q) => $q->latest('reading_datetime')->limit(10)]);

    // CRITICAL: Transform with Resource
    $meter->meter_data = MeterDataResource::collection($meter->meterData);

    return Inertia::render('meters/Show', ['meter' => $meter]);
}
```

**Before this fix:**
```php
// BAD: Direct model serialization
return Inertia::render('meters/Show', ['meter' => $meter]);
// Result: numeric fields returned as strings
```

**After this fix:**
```php
// GOOD: Resource transformation
$meter->meter_data = MeterDataResource::collection($meter->meterData);
return Inertia::render('meters/Show', ['meter' => $meter]);
// Result: ALL numeric fields guaranteed as floats
```

---

## LAYER 2: Frontend Type Safety

### Purpose
Catch any type mismatches that somehow make it through Layer 1. Provides runtime validation and logs errors for debugging.

### Implementation

#### 2.1 Schema Definitions (`resources/js/lib/schemas.ts`)

Using Zod, define the expected shape of API responses:

```typescript
import { z } from 'zod'

// Define what MeterData should look like
export const MeterDataSchema = z.object({
  id: z.number(),
  meter_name: z.string(),
  reading_datetime: z.string().or(z.date()),
  
  // Energy fields - MUST be numbers
  wh_total: z.number().nullable(),
  wh_delivered: z.number().nullable(),
  wh_received: z.number().nullable(),
  
  // Voltage fields - MUST be numbers
  vrms_a: z.number().nullable(),
  vrms_b: z.number().nullable(),
  vrms_c: z.number().nullable(),
  
  // Power fields - MUST be numbers
  watt: z.number().nullable(),
  va: z.number().nullable(),
  var: z.number().nullable(),
})

// Automatically infer TypeScript type from schema
export type MeterData = z.infer<typeof MeterDataSchema>
```

**How Zod works:**
```typescript
// Zod validates at runtime
const result = MeterDataSchema.parse(apiData)

// If data matches schema → returns typed data
// If data doesn't match → throws detailed error with path to bad field
```

#### 2.2 Validation Helper

```typescript
export function parseAndValidate<T>(
  schema: z.ZodSchema<T>,
  data: unknown,
  context: string
): T {
  try {
    return schema.parse(data)
  } catch (error) {
    if (error instanceof z.ZodError) {
      // Log which fields failed validation and why
      console.error(`Validation error in ${context}:`, error.errors)
      console.error('Invalid data:', data)
      // Return data as-is (don't crash the app)
      return data as T
    }
    throw error
  }
}
```

**Example output when validation fails:**
```
Validation error in meter show page: [
  {
    code: 'invalid_type',
    expected: 'number',
    received: 'string',
    path: ['meter_data', 0, 'wh_total'],  // Shows exact location
    message: 'Expected number, received string'
  }
]
Invalid data: { meter_data: [{ wh_total: "189804.80", ... }] }
```

#### 2.3 Usage in Vue Components

```typescript
// Before: No validation
const data = apiResponse.data
const formatted = data.wh_total / 1000  // Might be NaN if string!
const result = formatted.toFixed(2)      // ERROR if NaN

// After: With validation
import { MeterDataSchema, parseAndValidate } from '@/lib/schemas'

// Validate immediately when data arrives
const validatedData = parseAndValidate(
  MeterDataSchema,
  apiResponse.data,
  'meter detail page'
)

// Now we KNOW wh_total is a number
const formatted = (validatedData.wh_total || 0) / 1000
const result = formatted.toFixed(2)  // SAFE
```

#### 2.4 Safe Formatting Utilities

```typescript
// Format power with type safety
const formatPower = (watts: number | null | undefined) => {
  const w = watts || 0  // Default to 0 if null/undefined
  if (w >= 1000) {
    return { value: (w / 1000).toFixed(2), unit: 'kW' }
  }
  return { value: w.toFixed(2), unit: 'W' }
}

// Format energy with type safety
const formatEnergy = (value: number | null | undefined) => {
  return (value ? value / 1000 : 0).toFixed(2)
}

// Usage in template
<div>{{ formatEnergy(meter.meter_data[0].wh_total) }} kWh</div>
```

---

## LAYER 3: Process & Documentation

### Purpose
Ensure these patterns are followed consistently and prevent regressions when new features are added.

### Key Components

#### 3.1 TESTING_STRATEGY.md

Documents:
- Why each layer exists
- When to use each technique
- Prevention rules (DO's and DON'Ts)
- Checklist for adding new numeric fields

**Prevention Rules:**

```
1. Never directly serialize model without Resource
   ❌ return Inertia::render('Show', ['meter' => $meter])
   ✅ return Inertia::render('Show', ['meter' => new MeterResource($meter)])

2. Always validate API responses
   ❌ const value = apiResponse.value
   ✅ const value = parseAndValidate(Schema, apiResponse, 'context')

3. Cast at model level
   ✅ protected $casts = ['watt' => 'float']

4. Explicit transform in Resource
   ✅ 'watt' => $this->watt !== null ? (float) $this->watt : null
```

#### 3.2 Checklist for New Numeric Fields

When adding a new numeric field like `power_factor`:

1. **Model** (`app/Models/MeterData.php`)
   ```php
   protected $casts = ['power_factor' => 'float'];
   ```

2. **Resource** (`app/Http/Resources/MeterDataResource.php`)
   ```php
   'power_factor' => $this->power_factor !== null ? (float) $this->power_factor : null,
   ```

3. **Schema** (`resources/js/lib/schemas.ts`)
   ```typescript
   power_factor: z.number().nullable(),
   ```

4. **Test** (Add to feature test)
   ```php
   test('meter data returns power_factor as float', function () {
       $data = MeterDataResource::make($meterData)->resolve();
       $this->assertIsFloat($data['power_factor']);
   });
   ```

5. **Component** (Use safe formatters)
   ```typescript
   {{ (meterData.power_factor || 0).toFixed(3) }}
   ```

#### 3.3 Testing Strategy

**Feature Tests** - Verify API responses have correct types:
```php
test('meter data API returns numeric fields as floats', function () {
    $meterData = MeterData::factory()->create([
        'wh_total' => 123456.78,
        'watt' => 42.5,
    ]);
    
    $resource = new MeterDataResource($meterData);
    $data = $resource->resolve();
    
    $this->assertIsFloat($data['wh_total']);
    $this->assertIsFloat($data['watt']);
});
```

**Component Tests** - Verify Vue components handle edge cases:
```typescript
test('formatEnergy handles null values', () => {
  expect(formatEnergy(null)).toBe('0.00')
  expect(formatEnergy(undefined)).toBe('0.00')
  expect(formatEnergy(1000)).toBe('1.00')
})
```

---

## How the Layers Work Together

### Scenario 1: Everything Works (Normal Flow)

```
Backend Database:
  wh_total: "189804.80" (stored as string)
                    ↓
Eloquent Model Cast:
  wh_total: (float) 189804.80
                    ↓
Resource Transform:
  'wh_total' => (float) $this->wh_total  →  189804.80
                    ↓
API Response:
  { "wh_total": 189804.80 }  ✓ Float in JSON
                    ↓
Frontend Receives:
  wh_total: number (from JSON parse)
                    ↓
Zod Validation:
  ✓ Passes (number)
                    ↓
Vue Component:
  (189804.80 / 1000).toFixed(2)  →  "189.80" ✓
```

### Scenario 2: Model Casting Fails (Layer 1 Failure → Layer 2 Catches)

```
Backend Database:
  wh_total: "189804.80"
                    ↓
Eloquent Model Cast:
  (cast fails for some reason)
  wh_total: "189804.80"  (still string)
                    ↓
Resource Transform:
  'wh_total' => (float) $this->wh_total  →  189804.80  ✓ CATCHES IT!
                    ↓
API Response:
  { "wh_total": 189804.80 }  ✓ Float in JSON
                    ↓
Frontend:
  (189804.80 / 1000).toFixed(2)  →  "189.80" ✓
```

### Scenario 3: Resource Forgotten (Layer 1 & 2 Fail → Layer 2 Catches)

```
Backend:
  return Inertia::render('Show', ['meter' => $meter])
  (Direct model serialization, Resource not used)
  
API Response:
  { "wh_total": "189804.80" }  (string!)
                    ↓
Frontend Receives:
  wh_total: string (from JSON parse)
                    ↓
Zod Validation:
  ✗ FAILS! (expected number, got string)
  Logs: "Validation error in meter show page:
         Expected number, received string at path meter_data.0.wh_total"
                    ↓
Component Safely:
  formatEnergy(null) → "0.00"  (gracefully handles)
  ✓ Page doesn't crash, error is logged
```

---

## Common Patterns & Anti-patterns

### ✅ DO: Safe null handling in templates

```vue
<!-- Handles null, undefined, and NaN -->
<div>{{ (meterData?.wh_total || 0).toFixed(2) }} kWh</div>

<!-- Using safe formatter -->
<div>{{ formatEnergy(meterData?.wh_total) }} kWh</div>
```

### ❌ DON'T: Direct .toFixed() on potentially invalid data

```vue
<!-- Crashes if value is string or null -->
<div>{{ meterData.wh_total.toFixed(2) }} kWh</div>
```

### ✅ DO: Explicit casting in Resource

```php
'wh_total' => $this->wh_total !== null ? (float) $this->wh_total : null,
```

### ❌ DON'T: Implicit casting

```php
'wh_total' => $this->wh_total,  // Might be string!
```

### ✅ DO: Validate at API boundary

```typescript
const data = parseAndValidate(MeterDataSchema, apiResponse, 'meter show')
```

### ❌ DON'T: Assume types are correct

```typescript
const data = apiResponse.data  // No validation!
```

---

## Testing This System

### Run Type Checks

```bash
# Check TypeScript types
npm run typecheck

# Run ESLint
npm run lint
```

### Run Tests

```bash
# All tests
composer test

# Specific resource test
php artisan test --filter=MeterDataResourceTest

# Specific component test
npm run test:run
```

### Manual Testing

1. **Check Network Tab** - Verify API response has numeric fields
   - Open browser DevTools → Network
   - Load meter detail page
   - Click network request to API
   - Check Response → should see `"wh_total": 189804.80` (not `"189804.80"`)

2. **Check Console** - Verify no validation errors
   - Open browser DevTools → Console
   - No "Validation error" messages should appear
   - Look for "Show.vue mounted" debug logs

3. **Test Edge Cases**
   - Meter with null wh_total
   - Meter with 0 wh_total
   - Meter with very large number (>1000000)

---

## Performance Implications

### Layer 1 (Backend Casting)
- **Negligible cost** - PHP native cast, microseconds
- Minimal memory overhead

### Layer 2 (Frontend Validation)
- **Zod validation cost** - ~1-5ms per parse
- Only happens once when component mounts
- Validation cache prevents redundant checks
- **Benefit outweighs cost** - Early error detection

### Layer 3 (Documentation)
- **Zero runtime cost** - Only affects development

---

## When to Add New Validation

Add validation for:
- Any field that's displayed or used in calculations
- Fields that could be null
- Fields that users might depend on for critical logic

Don't validate:
- Fields only for debugging
- Very large collections (> 1000 items) where parsing overhead matters
- Static configuration values

---

## Migration Guide: Existing Code

If you have existing API endpoints returning untyped data:

1. **Create Resource class** for the model
2. **Add casts** to model for all numeric fields
3. **Update controller** to use Resource
4. **Add Zod schema** for response validation
5. **Update components** to use parseAndValidate()
6. **Add tests** to prevent regression

Example for a hypothetical `PowerReading` model:

```bash
# 1. Create resource
php artisan make:resource PowerReadingResource

# 2. Add to model
protected $casts = ['power' => 'float', 'voltage' => 'float'];

# 3. Update controller
$data = PowerReadingResource::make($reading);

# 4. Add schema
export const PowerReadingSchema = z.object({
  power: z.number(),
  voltage: z.number(),
})

# 5. Update component
const data = parseAndValidate(PowerReadingSchema, response, 'power reading')
```

---

## References

- **Zod**: https://zod.dev - TypeScript-first schema validation
- **Laravel Resources**: https://laravel.com/docs/resources
- **Eloquent Casting**: https://laravel.com/docs/eloquent-mutators#attribute-casting
- **Vue Ref Unwrapping**: https://vuejs.org/guide/extras/reactivity-in-depth.html#ref-unwrapping-in-templates
