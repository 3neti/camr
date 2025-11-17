# Table Column Configuration

This document describes the centralized table column configuration system for all index tables in the CAMR application.

## Overview

All index table columns (Sites, Buildings, Locations, Gateways, Meters, Configuration Files, Users) are now configured in a single, centralized configuration file. This provides:

- **Single source of truth** for column definitions
- **Environment-specific customization** without committing changes
- **Consistent column behavior** across all tables
- **Configurable properties**: visibility, order, width, alignment, sortability, and formatters
- **User preferences preserved** via localStorage

## Configuration File

### Location
```
resources/js/config/tableColumns.ts
```

### Git Handling
- **NOT committed** to version control (added to `.gitignore`)
- **Example template**: `resources/js/config/tableColumns.example.ts` (committed)
- Each environment can copy the example file and customize as needed
- **Automatic fallback**: If `tableColumns.ts` doesn't exist, the example file is used automatically

### Initial Setup

The application will work immediately on fresh installations by using the example configuration as a fallback. However, for production environments, you should create your own customized configuration:

```bash
# Copy the example file to create your environment-specific config
cp resources/js/config/tableColumns.example.ts resources/js/config/tableColumns.ts

# Edit the file to customize for your environment
# This file will not be tracked by Git
```

**Development Note**: When running in development mode, the console will display:
- `[TableColumns] ✓ Using environment-specific configuration` - if custom config exists
- `[TableColumns] ⚠ Using example configuration` - if falling back to example file

## Column Properties

Each column can be configured with the following properties:

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `key` | `string` | Yes | Unique identifier matching the data property name |
| `label` | `string` | Yes | Display name for the column header |
| `visible` | `boolean` | Yes | Default visibility state |
| `order` | `number` | Yes | Display order (lower numbers appear first) |
| `locked` | `boolean` | No | If true, column cannot be hidden by users |
| `sortable` | `boolean` | No | If true, column supports sorting |
| `width` | `string` | No | CSS width value (e.g., '200px', '20%', 'auto') |
| `alignment` | `'left' \| 'center' \| 'right'` | No | Text alignment |
| `formatter` | `'date' \| 'datetime' \| 'currency' \| 'boolean' \| 'badge' \| 'custom'` | No | Value formatter type |

## Configuration Structure

```typescript
export interface ColumnConfig {
  key: string
  label: string
  visible: boolean
  locked?: boolean
  sortable?: boolean
  order: number
  width?: string
  alignment?: ColumnAlignment
  formatter?: ColumnFormatter
}

export interface TableConfig {
  storageKey: string
  columns: ColumnConfig[]
}
```

## Example Configuration

```typescript
const tableConfigs: Record<string, TableConfig> = {
  sites: {
    storageKey: 'sites-column-preferences',
    columns: [
      {
        key: 'checkbox',
        label: 'Select',
        visible: true,
        locked: true,        // Cannot be hidden by users
        sortable: false,
        order: 0,            // First column
        width: '48px',       // Fixed width
        alignment: 'center',
      },
      {
        key: 'code',
        label: 'Code',
        visible: true,
        locked: true,
        sortable: true,      // Can be sorted
        order: 1,
        width: 'auto',       // Flexible width
        alignment: 'left',
      },
      {
        key: 'status',
        label: 'Status',
        visible: true,
        locked: false,       // Can be hidden by users
        sortable: false,
        order: 4,
        width: '120px',
        alignment: 'left',
        formatter: 'badge',  // Display as badge
      },
    ],
  },
  // ... other tables
}
```

## Available Tables

The following tables are configured:

1. **Sites** (`sites`) - 7 columns
2. **Buildings** (`buildings`) - 5 columns
3. **Locations** (`locations`) - 6 columns
4. **Gateways** (`gateways`) - 8 columns
5. **Meters** (`meters`) - 9 columns
6. **Configuration Files** (`configFiles`) - 4 columns
7. **Users** (`users`) - 8 columns

## Usage in Components

### Basic Usage

```vue
<script setup lang="ts">
import { useColumnPreferences } from '@/composables/useColumnPreferences'
import { getTableConfig } from '@/config/tableColumns'

// Get table configuration
const tableConfig = getTableConfig('sites')!

// Initialize column preferences
const columnPrefs = useColumnPreferences({
  storageKey: tableConfig.storageKey,
  defaultColumns: tableConfig.columns,
})
</script>

<template>
  <TableHead v-if="columnPrefs.isColumnVisible('status')">
    Status
  </TableHead>
  
  <TableCell v-if="columnPrefs.isColumnVisible('status')">
    {{ site.status_label }}
  </TableCell>
</template>
```

### Using Column Properties

```vue
<script setup lang="ts">
// Access individual column properties
const statusWidth = columnPrefs.getColumnWidth('status')       // '120px'
const statusAlignment = columnPrefs.getColumnAlignment('status') // 'left'
const statusFormatter = columnPrefs.getColumnFormatter('status') // 'badge'

// Get all visible columns (sorted by order)
const visibleCols = columnPrefs.visibleColumns.value

// Get sortable columns
const sortableCols = columnPrefs.sortableColumns.value
</script>
```

## How It Works

### 1. Configuration Loading

**Automatic Fallback Mechanism:**

The system uses a smart loader (`tableColumnsLoader.ts`) that automatically handles missing configuration files:

1. **On application startup**, the loader attempts to import `tableColumns.ts`
2. **If the file exists**, it uses the custom configuration
3. **If the file is missing**, it automatically falls back to `tableColumns.example.ts`
4. **No errors or crashes** occur on fresh installations

**Component Loading Flow:**

When an Index.vue component loads:
1. Imports the config using `getTableConfig('tableName')` from the **loader** (not directly from tableColumns.ts)
2. Passes the config to `useColumnPreferences` composable
3. Composable merges config defaults with user's localStorage preferences

### 2. Column Ordering
- Columns are automatically sorted by their `order` property
- Lower `order` numbers appear first
- The `sortedColumns` computed property provides ordered columns

### 3. User Preferences
- Users can show/hide toggleable columns (not `locked`)
- Preferences are saved to localStorage
- Config provides initial defaults
- User preferences override config defaults

### 4. Locked Columns
- Columns with `locked: true` always appear
- Cannot be hidden via the ColumnPreferences UI
- Examples: checkbox, actions, primary identifier columns

## Customization Guide

### Change Column Visibility
```typescript
{
  key: 'division',
  label: 'Division',
  visible: false,  // Hidden by default
  // ...
}
```

### Reorder Columns
```typescript
// Swap order of status and last_update
{
  key: 'status',
  order: 5,  // Was 4
  // ...
},
{
  key: 'last_update',
  order: 4,  // Was 5
  // ...
}
```

### Change Column Width
```typescript
{
  key: 'status',
  width: '150px',  // Was '120px'
  // ...
}
```

### Make Column Sortable
```typescript
{
  key: 'division',
  sortable: true,  // Was false
  // ...
}
```

### Change Alignment
```typescript
{
  key: 'meters_using',
  alignment: 'right',  // Was 'center'
  // ...
}
```

## localStorage Format

User preferences are stored in localStorage with keys defined in `storageKey`:

```json
{
  "sites-column-preferences": {
    "checkbox": true,
    "code": true,
    "company": false,
    "division": true,
    "status": true,
    "last_update": true,
    "actions": true
  }
}
```

## API Reference

### Functions

#### `getTableConfig(tableName: string): TableConfig | undefined`
Retrieves the configuration for a specific table.

```typescript
const sitesConfig = getTableConfig('sites')
```

#### `getAllTableConfigs(): Record<string, TableConfig>`
Returns all table configurations.

```typescript
const allConfigs = getAllTableConfigs()
```

#### `isExampleConfig(): boolean`
Checks if the application is currently using the example configuration (not customized).
Useful for displaying setup warnings in admin panels.

```typescript
import { isExampleConfig } from '@/config/tableColumnsLoader'

if (isExampleConfig()) {
  console.warn('Using example configuration - please customize for production')
  // Show admin warning banner
}
```

### Composable: `useColumnPreferences`

#### Returned Properties

| Property | Type | Description |
|----------|------|-------------|
| `columns` | `Ref<TableColumn[]>` | All columns with user preferences applied |
| `sortedColumns` | `ComputedRef<TableColumn[]>` | Columns sorted by order |
| `visibleColumns` | `ComputedRef<TableColumn[]>` | Only visible columns (sorted) |
| `hiddenColumns` | `ComputedRef<TableColumn[]>` | Only hidden columns (sorted) |
| `lockedColumns` | `ComputedRef<TableColumn[]>` | Only locked columns |
| `toggleableColumns` | `ComputedRef<TableColumn[]>` | Only non-locked columns |
| `sortableColumns` | `ComputedRef<TableColumn[]>` | Only sortable columns |

#### Returned Methods

| Method | Description |
|--------|-------------|
| `isColumnVisible(key)` | Check if a column is visible |
| `toggleColumn(key)` | Toggle column visibility |
| `showColumn(key)` | Show a column |
| `hideColumn(key)` | Hide a column |
| `showAllColumns()` | Show all toggleable columns |
| `hideAllColumns()` | Hide all toggleable columns |
| `resetToDefaults()` | Reset all columns to visible |
| `getColumn(key)` | Get column configuration |
| `getColumnWidth(key)` | Get column width |
| `getColumnAlignment(key)` | Get column alignment |
| `getColumnFormatter(key)` | Get column formatter |

## Best Practices

1. **Locked Columns**: Always lock columns that are essential for table functionality (checkbox, actions, primary identifiers)

2. **Order Numbers**: Use increments of 10 (0, 10, 20...) to make inserting new columns easier

3. **Width Values**: 
   - Use `'auto'` for flexible content-based width
   - Use fixed pixel values (`'120px'`) for badges, icons, or fixed-width content
   - Use `'48px'` for checkbox columns

4. **Alignment**:
   - Left-align text content
   - Center-align icons, badges, counts
   - Right-align actions

5. **Testing**: After modifying the config, test all affected index pages to ensure proper rendering

## Troubleshooting

### Columns not appearing in correct order
- Check that `order` values are unique and sequential
- Ensure no two columns have the same `order` value
- Clear localStorage and refresh: `localStorage.clear()`

### TypeScript errors
- Ensure all required properties are present
- Check that `alignment` values are valid (`'left'` | `'center'` | `'right'`)
- Verify `formatter` values match defined types

### Changes not reflecting
- Hard refresh the browser (Cmd+Shift+R / Ctrl+Shift+R)
- Clear localStorage for the specific table
- Rebuild frontend assets: `npm run build`

### User preferences not saving
- Check browser console for errors
- Verify `storageKey` is unique for each table
- Ensure localStorage is not full or disabled

## Migration Notes

### From Hardcoded to Centralized Config

The previous implementation had column definitions duplicated in two places per Index.vue file:
1. In the `useColumnPreferences` call
2. In the `<ColumnPreferences>` component props

Now, columns are defined once in `tableColumns.ts` and referenced from both locations:

**Before:**
```vue
const columnPrefs = useColumnPreferences({
  storageKey: 'sites-column-preferences',
  defaultColumns: [
    { key: 'code', label: 'Code', locked: true },
    // ... more columns
  ],
})

<ColumnPreferences
  storage-key="sites-column-preferences"
  :default-columns="[
    { key: 'code', label: 'Code', locked: true },
    // ... duplicate definitions
  ]"
/>
```

**After:**
```vue
const tableConfig = getTableConfig('sites')!
const columnPrefs = useColumnPreferences({
  storageKey: tableConfig.storageKey,
  defaultColumns: tableConfig.columns,
})

<ColumnPreferences
  :storage-key="tableConfig.storageKey"
  :default-columns="tableConfig.columns"
/>
```

## Future Enhancements

Possible future improvements:

1. **Drag-and-Drop Reordering**: Allow users to reorder columns visually
2. **Admin Override**: Database-stored configs that override file-based configs
3. **Column Resizing**: Allow users to resize column widths
4. **Conditional Visibility**: Show/hide columns based on user roles or permissions
5. **Column Groups**: Group related columns together
6. **Export Presets**: Save and load column configuration presets

## Support

For questions or issues related to table column configuration, please:

1. Check this documentation
2. Review the example config file: `tableColumns.example.ts`
3. Test in development environment before deploying
4. Clear browser cache and localStorage if experiencing issues

---

**Last Updated**: 2025-11-17
**Version**: 1.0.0
