# Testing Guide: Site Context Filtering

This guide will help you test the session-based site context filtering feature.

## What Was Fixed

The site context filtering now properly persists selected sites across page navigation using Laravel sessions. Previously, selections were lost when navigating between pages.

## How to Test

### Test 1: Basic Selection and Navigation

1. **Start the development server:**
   ```bash
   composer dev
   ```

2. **Navigate to Sites page:**
   - Go to `/sites`
   - You should see a list of sites

3. **Select a site:**
   - Click on any site row to select it
   - The row should highlight (background changes)
   - You should see "Selected (1)" with action buttons appear

4. **Navigate to Gateways:**
   - Click the 📡 (Radio) icon in the "Selected (1)" section
   - You should be taken to `/gateways?site_id=X`
   - The Site filter dropdown should show the selected site (not "All Sites")
   - The gateway list should be filtered to that site

5. **Navigate back to Sites:**
   - Click "Sites" in the sidebar navigation
   - The previously selected site should still be highlighted/selected

### Test 2: Filter Persistence Across Multiple Pages

1. **Select a site on Sites page**
2. **Navigate to Buildings** (click 🏢 icon)
   - Site filter should show your selected site
3. **Navigate to Locations** (via sidebar)
   - Site filter should still show your selected site
4. **Navigate to Meters** (via sidebar)
   - Site filter should still show your selected site
5. **Go back to Sites**
   - Selected site should still be highlighted

### Test 3: Clearing the Context

1. **Select a site on Sites page**
2. **Navigate to Gateways**
   - Site filter shows the selected site
3. **Change the filter to "All Sites"**
   - Select "All Sites" from the Site dropdown
   - The session should be cleared
4. **Navigate back to Sites**
   - No site should be selected (no highlighted rows)

### Test 4: Page Refresh

1. **Select a site on Sites page**
2. **Navigate to Gateways**
3. **Refresh the page (F5 or Cmd+R)**
   - The site filter should still show your selected site
   - This proves the context is stored server-side in Laravel session

### Test 5: Multiple Selections (Should Only Keep Last One)

1. **Select Site A on Sites page**
2. **Navigate to Gateways**
   - Filter shows Site A
3. **Navigate back to Sites**
4. **Select Site B** (different site)
5. **Navigate to Gateways again**
   - Filter should now show Site B (not Site A)

## Expected Behavior

✅ **Should work:**
- Selecting a site on Sites page highlights the row
- Navigating to related pages (Gateways, Buildings, Locations, Meters) shows the selected site in the filter
- Returning to Sites page restores the selection visual feedback
- Refreshing any page maintains the filter
- Selecting "All Sites" in any dropdown clears the context

❌ **Should NOT happen:**
- Losing selection when navigating between pages
- Filter showing "All Sites" when a site was selected
- Selection persisting after explicitly choosing "All Sites"

## How It Works (Technical Overview)

```
User Action: Click site row on Sites page
     ↓
Frontend: router.get('/gateways', { site_id: 123 })
     ↓
Middleware: ManageSiteContext captures site_id=123
     ↓
Session: Stores selected_site_id = 123
     ↓
Middleware: HandleInertiaRequests shares selectedSiteId with frontend
     ↓
Frontend: useSiteContext() reads selectedSiteId from Inertia props
     ↓
Pages: Initialize filters from selectedSiteId → filters.site_id → 'all'
     ↓
Sites Page: onMounted() restores selection visually using bulk.toggleSelection()
```

## Debugging

If something doesn't work:

1. **Check Laravel session:**
   ```php
   // Add to any controller temporarily
   dd(session('selected_site_id'));
   ```

2. **Check Inertia props:**
   - Open browser DevTools → Vue DevTools
   - Look at page props → `selectedSiteId` should show the site ID

3. **Check middleware order:**
   ```php
   // In bootstrap/app.php
   ManageSiteContext::class,  // MUST be before HandleInertiaRequests
   HandleInertiaRequests::class,
   ```

4. **Check console for errors:**
   - Open browser DevTools → Console
   - Look for any JavaScript errors

## Files Involved

- **Middleware:** `app/Http/Middleware/ManageSiteContext.php`
- **Composable:** `resources/js/composables/useSiteContext.ts`
- **Pages:**
  - `resources/js/pages/sites/Index.vue`
  - `resources/js/pages/gateways/Index.vue`
  - `resources/js/pages/buildings/Index.vue`
  - `resources/js/pages/locations/Index.vue`
  - `resources/js/pages/meters/Index.vue`
- **Shared Data:** `app/Http/Middleware/HandleInertiaRequests.php`
- **Config:** `bootstrap/app.php`

## Related Documentation

- [Site Context Filtering Documentation](./SITE_CONTEXT_FILTERING.md)
