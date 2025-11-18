# Site Context Filtering

This document explains how the site context filtering system works in the CAMR application.

## Overview

The site context filtering feature allows users to select a site and have that selection persist across different pages (Buildings, Locations, Gateways, Meters). This creates a seamless workflow for drilling down from Sites to related entities.

## How It Works

### 1. Laravel Session Storage

The selected site ID is stored in the **Laravel session** (server-side), not in cookies or browser localStorage. This provides:

- ✅ **Persistence** - Survives page refreshes and navigation
- ✅ **Security** - Server-side storage is more secure
- ✅ **Reliability** - Not affected by browser storage limits or settings
- ✅ **Simplicity** - Automatically available in all controllers

### 2. Middleware: `ManageSiteContext`

**Location**: `app/Http/Middleware/ManageSiteContext.php`

This middleware runs on every web request and:

1. **Captures** `site_id` from the request query parameters
2. **Stores** it in the session: `session('selected_site_id', $siteId)`
3. **Applies** it to subsequent requests that don't have `site_id`
4. **Shares** it with all views via `view()->share()`

**Workflow:**

```php
// User visits: /gateways?site_id=1
if ($request->has('site_id')) {
    $request->session()->put('selected_site_id', $request->site_id);
}

// User then visits: /meters (no site_id param)
if (!$request->has('site_id') && $request->session()->has('selected_site_id')) {
    $request->merge(['site_id' => $request->session()->get('selected_site_id')]);
}
```

### 3. Shared Data via Inertia

The selected site ID is shared with all Inertia pages via `HandleInertiaRequests`:

```php
'selectedSiteId' => $request->session()->get('selected_site_id')
```

This makes it available in Vue components via `$page.props.selectedSiteId`.

### 4. Controller Integration

Controllers automatically receive the `site_id` from the middleware, so they can use it directly:

```php
public function index(Request $request): Response
{
    $query = Gateway::query()
        ->when($request->site_id, function ($query, $siteId) {
            $query->where('site_id', $siteId);
        });
    
    // ... rest of controller
}
```

## User Experience

### Workflow Example:

```
1. User on Sites page
   ↓
2. Click on "SITE-001" row → Selected (1)
   ↓
3. Click 📡 (Gateways icon)
   ↓
4. Redirects to /gateways?site_id=1
   ↓
5. Middleware stores site_id=1 in session
   ↓
6. Gateways page shows filtered results
   ↓
7. User clicks "Meters" in nav
   ↓
8. /meters (no site_id in URL)
   ↓
9. Middleware auto-applies site_id=1 from session
   ↓
10. Meters page also shows filtered results!
```

## Clearing the Context

To clear the selected site context, pass `site_id=clear` or `site_id=null`:

```php
// In a controller or route
router.get('/gateways', { site_id: 'clear' })
```

Or visit any page with "All Sites" selected in the filter dropdown.

## Implementation Files

### Backend

1. **Middleware**: `app/Http/Middleware/ManageSiteContext.php`
   - Manages session storage and retrieval
   - Registered in `bootstrap/app.php`

2. **Inertia Middleware**: `app/Http/Middleware/HandleInertiaRequests.php`
   - Shares `selectedSiteId` with frontend

3. **Controllers**: All index controllers (Sites, Buildings, Locations, Gateways, Meters)
   - Use `$request->site_id` to filter results
   - No special session handling needed

### Frontend

1. **Sites Index**: `resources/js/pages/sites/Index.vue`
   - Navigation buttons pass `site_id` to target pages
   - Functions: `goToBuildings()`, `goToLocations()`, `goToGateways()`, `goToMeters()`

2. **Filter Dropdowns**: All Index pages have Site filter dropdowns
   - Automatically pre-select based on `props.filters.site_id`

## Technical Details

### Session Key

```
'selected_site_id' => 1  // Integer site ID
```

### Middleware Order

```php
$middleware->web(append: [
    HandleAppearance::class,
    ManageSiteContext::class,        // ← Runs BEFORE Inertia
    HandleInertiaRequests::class,
    AddLinkHeadersForPreloadedAssets::class,
]);
```

The order is important: `ManageSiteContext` must run before `HandleInertiaRequests` so the session value is available for sharing.

### Session Lifetime

The selected site context follows Laravel's session configuration:

- **Default**: Lifetime set in `config/session.php`
- **Typical**: 120 minutes (2 hours)
- **Storage**: File-based by default, can be configured for Redis, database, etc.

## Benefits

### For Users:
- ✅ Seamless navigation between related pages
- ✅ Context is preserved automatically
- ✅ No need to re-select site on each page
- ✅ Intuitive workflow for data exploration

### For Developers:
- ✅ Simple implementation in controllers
- ✅ No complex state management needed
- ✅ Standard Laravel patterns
- ✅ Easy to extend to other contexts (e.g., date ranges, filters)

## Extending the System

### Adding More Context

You can extend this pattern to store other context:

```php
// In ManageSiteContext middleware
if ($request->has('date_range')) {
    $request->session()->put('selected_date_range', $request->date_range);
}
```

### Multiple Sites Selection

Currently supports single site selection. To support multiple:

```php
// Store as array
$request->session()->put('selected_site_ids', $request->site_ids);

// Use whereIn() in controllers
$query->whereIn('site_id', $request->site_ids);
```

### Context Indicator UI

Add a banner showing the active filter:

```vue
<div v-if="$page.props.selectedSiteId" class="bg-primary/10 p-2">
  Filtering by Site ID: {{ $page.props.selectedSiteId }}
  <button @click="router.get(route, { site_id: 'clear' })">Clear</button>
</div>
```

## Troubleshooting

### Context not persisting

**Symptom**: Selected site doesn't carry over to next page

**Check**:
1. Middleware registered in `bootstrap/app.php`
2. Session driver configured properly in `.env`
3. Session cookies are being set (check browser dev tools)

### Filter dropdown not showing selected site

**Symptom**: Dropdown shows "All Sites" even though filtering is active

**Check**:
1. Controller passes `site_id` in filters: `'filters' => $request->only(['site_id', ...])`
2. Frontend initializes from filters: `const siteId = ref(props.filters.site_id?.toString() || 'all')`

### Session cleared unexpectedly

**Causes**:
- Logging out (sessions are flushed)
- Session timeout (check `SESSION_LIFETIME` in `.env`)
- Explicitly passing `site_id=clear`

## Best Practices

1. **Always pass filters to views**:
   ```php
   'filters' => $request->only(['search', 'site_id', 'status'])
   ```

2. **Initialize refs from filters**:
   ```typescript
   const siteId = ref(props.filters.site_id?.toString() || 'all')
   ```

3. **Provide clear button**:
   ```vue
   <Button @click="router.get(route, { site_id: 'clear' })">
     Clear Site Filter
   </Button>
   ```

4. **Show visual feedback**:
   ```vue
   <Badge v-if="props.filters.site_id">
     Filtered by Site
   </Badge>
   ```

## Security Considerations

- ✅ **Server-side**: Session stored on server, not in client
- ✅ **Validation**: Always validate site_id in controllers
- ✅ **Authorization**: Check user has access to the selected site
- ✅ **Sanitization**: Integer IDs are safe, but validate anyway

```php
// Example validation in controller
$validated = $request->validate([
    'site_id' => 'nullable|integer|exists:sites,id'
]);
```

---

**Last Updated**: 2025-11-18  
**Version**: 1.0.0
