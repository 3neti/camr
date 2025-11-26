# CAMR Frontend Patterns

This document describes frontend architecture, component patterns, and conventions used in the CAMR application.

## Technology Stack

- **Vue 3** with Composition API and `<script setup>` syntax
- **TypeScript** for type safety
- **Inertia.js v2** for SPA-like experience without separate API
- **Tailwind CSS v4** for styling
- **shadcn-vue (Reka UI)** for accessible UI components
- **Vite** for fast development and building
- **Laravel Wayfinder** for type-safe routing

## Project Structure

```
resources/js/
├── actions/          # Generated Wayfinder route actions (gitignored)
├── components/       # Custom application components
│   ├── ui/          # shadcn-vue components (auto-generated)
│   ├── AppHeader.vue
│   ├── AppSidebar.vue
│   └── ...
├── composables/     # Reusable Vue composition functions
│   ├── useAppearance.ts
│   └── ...
├── config/          # Frontend configuration
│   └── tableColumns.ts
├── layouts/         # Page layouts
│   ├── AppLayout.vue
│   ├── AuthLayout.vue
│   ├── app/        # App layout partials
│   ├── auth/       # Auth layout partials
│   └── settings/   # Settings layout partials
├── lib/            # Utility functions
│   └── utils.ts
├── pages/          # Inertia page components
│   ├── Dashboard.vue
│   ├── sites/
│   ├── buildings/
│   ├── locations/
│   ├── gateways/
│   ├── meters/
│   ├── settings/
│   └── auth/
├── routes/         # Generated Wayfinder routes (gitignored)
├── types/          # TypeScript type definitions
│   └── index.d.ts
├── app.ts          # Main Vue app setup
└── ssr.ts          # SSR entry point
```

## Path Aliases

Configured in `tsconfig.json` and `vite.config.ts`:

- `@/` → `resources/js/`
- `@/components` → `resources/js/components`
- `@/layouts` → `resources/js/layouts`
- `@/composables` → `resources/js/composables`
- `@/lib` → `resources/js/lib`
- `@/ui` → `resources/js/components/ui`

Always use these aliases for imports:

```typescript
// Good
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/ui/button'
import { useAppearance } from '@/composables/useAppearance'

// Bad
import AppLayout from '../layouts/AppLayout.vue'
import { Button } from './components/ui/button'
```

## Inertia.js Patterns

### Page Components

Pages are Vue components in `resources/js/pages/` that correspond to `Inertia::render()` calls in controllers.

**Controller:**
```php
// app/Http/Controllers/SiteController.php
public function index()
{
    return Inertia::render('sites/Index', [
        'sites' => Site::all(),
        'selectedSiteId' => session('selected_site_id'),
    ]);
}
```

**Page:**
```vue
<!-- resources/js/pages/sites/Index.vue -->
<script setup lang="ts">
import { defineProps } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

interface Props {
  sites: Array<Site>
  selectedSiteId?: number
}

const props = defineProps<Props>()
</script>

<template>
  <AppLayout>
    <!-- Page content -->
  </AppLayout>
</template>
```

### Navigation

Use `<Link>` component or `router.visit()` for navigation:

```vue
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { dashboard, sites } from '@/routes'

// Navigate programmatically
const goToDashboard = () => {
  router.visit(dashboard().url)
}
</script>

<template>
  <!-- Declarative navigation -->
  <Link :href="sites.index().url">Sites</Link>
  
  <!-- With active state -->
  <Link 
    :href="sites.index().url"
    :class="{ 'active': $page.component === 'sites/Index' }"
  >
    Sites
  </Link>
</template>
```

### Form Handling

Use Inertia's `<Form>` component for form submissions:

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { sites } from '@/routes'
import { Button } from '@/ui/button'
import { Input } from '@/ui/input'
</script>

<template>
  <Form
    v-bind="sites.store.form()"
    #default="{ errors, processing }"
  >
    <Input 
      name="code" 
      placeholder="Site Code"
      :error="errors.code"
    />
    
    <Button type="submit" :disabled="processing">
      {{ processing ? 'Creating...' : 'Create Site' }}
    </Button>
  </Form>
</template>
```

### Props and Types

Always define TypeScript interfaces for page props:

```typescript
// resources/js/types/index.d.ts
export interface Site {
  id: number
  code: string
  name?: string
  company_id?: number
  division_id?: number
  latitude?: number
  longitude?: number
  created_at: string
  updated_at: string
}

export interface PaginatedData<T> {
  data: T[]
  current_page: number
  per_page: number
  total: number
  last_page: number
}
```

Use in pages:

```vue
<script setup lang="ts">
import type { Site, PaginatedData } from '@/types'

interface Props {
  sites: PaginatedData<Site>
  filters: {
    search?: string
    status?: string
  }
}

const props = defineProps<Props>()
</script>
```

## Laravel Wayfinder Usage

Wayfinder generates type-safe route functions from Laravel routes.

### Basic Usage

```typescript
import { dashboard, sites, buildings } from '@/routes'

// Get route URL
const url = sites.index().url // "/sites"

// Get route with parameters
const showUrl = sites.show.url(1) // "/sites/1"

// Get full route object (url + method)
const route = sites.store() // { url: "/sites", method: "post" }

// With query parameters
const filtered = sites.index({ query: { status: 'online' } }).url
// "/sites?status=online"

// Merge with existing query params
const merged = sites.index({ mergeQuery: { page: 2 } }).url
// Merges with window.location.search
```

### Form Binding

Use `.form()` for HTML form attributes:

```vue
<template>
  <Form v-bind="sites.store.form()">
    <!-- method="post" action="/sites" automatically set -->
  </Form>
</template>
```

### Importing Routes

```typescript
// Named imports for tree-shaking
import { index, show, store, update, destroy } from '@/routes/sites'

// Or import specific controllers
import { index as siteIndex } from '@/routes/sites'
import { index as buildingIndex } from '@/routes/buildings'
```

## Layouts

### AppLayout

Main authenticated application layout with sidebar, header, and footer.

```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'

const breadcrumbs = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Sites', href: '/sites' },
  { label: 'Site Details' },
]
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <h1>Page Title</h1>
    </template>
    
    <!-- Main content -->
    <div>Page content here</div>
  </AppLayout>
</template>
```

### AuthLayout

Layout for authentication pages (login, register, forgot password).

```vue
<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue'
</script>

<template>
  <AuthLayout>
    <div class="max-w-md">
      <!-- Auth form here -->
    </div>
  </AuthLayout>
</template>
```

## UI Components (shadcn-vue)

The application uses shadcn-vue components from `@/components/ui/`.

### Common Components

```vue
<script setup lang="ts">
import { Button } from '@/ui/button'
import { Input } from '@/ui/input'
import { Label } from '@/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/ui/card'
import { Badge } from '@/ui/badge'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/ui/dialog'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/ui/table'
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Card Title</CardTitle>
    </CardHeader>
    <CardContent>
      <form>
        <div class="space-y-4">
          <div>
            <Label for="name">Name</Label>
            <Input id="name" type="text" />
          </div>
          
          <Button type="submit">Submit</Button>
        </div>
      </form>
    </CardContent>
  </Card>
</template>
```

### Status Badges

```vue
<template>
  <!-- Online status -->
  <Badge variant="success">Online</Badge>
  
  <!-- Offline status -->
  <Badge variant="destructive">Offline</Badge>
  
  <!-- Pending status -->
  <Badge variant="warning">Pending</Badge>
</template>
```

## Custom Component Patterns

### Table Components

Tables with search, filter, sort, and pagination:

```vue
<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/ui/table'
import { Input } from '@/ui/input'
import { Button } from '@/ui/button'

interface Props {
  data: PaginatedData<Site>
  filters: {
    search?: string
  }
}

const props = defineProps<Props>()

const search = ref(props.filters.search || '')

const performSearch = () => {
  router.get('/sites', { search: search.value }, {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex gap-4">
      <Input 
        v-model="search" 
        placeholder="Search..."
        @keyup.enter="performSearch"
      />
      <Button @click="performSearch">Search</Button>
    </div>
    
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Code</TableHead>
          <TableHead>Name</TableHead>
          <TableHead>Status</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow v-for="site in data.data" :key="site.id">
          <TableCell>{{ site.code }}</TableCell>
          <TableCell>{{ site.name }}</TableCell>
          <TableCell>
            <Badge :variant="site.status ? 'success' : 'destructive'">
              {{ site.status ? 'Online' : 'Offline' }}
            </Badge>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
  </div>
</template>
```

### Status Indicators

```vue
<script setup lang="ts">
import { Badge } from '@/ui/badge'

interface Props {
  online: boolean
  lastUpdate?: string
}

const props = defineProps<Props>()
</script>

<template>
  <div class="flex items-center gap-2">
    <span 
      class="h-2 w-2 rounded-full"
      :class="online ? 'bg-green-500' : 'bg-red-500'"
    />
    <Badge :variant="online ? 'success' : 'destructive'">
      {{ online ? 'Online' : 'Offline' }}
    </Badge>
    <span v-if="lastUpdate" class="text-sm text-muted-foreground">
      Last seen {{ lastUpdate }}
    </span>
  </div>
</template>
```

## Composables

### useAppearance

Manages theme (light/dark mode):

```typescript
// resources/js/composables/useAppearance.ts
import { ref, watch } from 'vue'

export function useAppearance() {
  const theme = ref<'light' | 'dark' | 'auto'>('auto')
  
  const setTheme = (newTheme: 'light' | 'dark' | 'auto') => {
    theme.value = newTheme
    // Apply theme to document
    // Save to localStorage
  }
  
  return {
    theme,
    setTheme,
  }
}
```

Usage:

```vue
<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance'

const { theme, setTheme } = useAppearance()
</script>

<template>
  <Button @click="setTheme('dark')">Dark Mode</Button>
</template>
```

## Styling with Tailwind CSS v4

### Utility Classes

Use Tailwind utility classes for styling:

```vue
<template>
  <!-- Layout -->
  <div class="flex flex-col gap-4 p-6">
    
    <!-- Card -->
    <div class="rounded-lg border bg-card p-6 shadow-sm">
      
      <!-- Typography -->
      <h2 class="text-2xl font-bold">Title</h2>
      <p class="text-muted-foreground">Description</p>
      
      <!-- Spacing -->
      <div class="mt-4 space-y-2">
        <div>Item 1</div>
        <div>Item 2</div>
      </div>
    </div>
  </div>
</template>
```

### Dark Mode

Support dark mode with `dark:` prefix:

```vue
<template>
  <div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    <h1 class="text-gray-900 dark:text-white">Title</h1>
    <p class="text-gray-600 dark:text-gray-400">Description</p>
  </div>
</template>
```

### Responsive Design

Use responsive prefixes:

```vue
<template>
  <!-- Mobile first -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div>Column 1</div>
    <div>Column 2</div>
    <div>Column 3</div>
  </div>
  
  <!-- Hide on mobile -->
  <div class="hidden md:block">Desktop only</div>
  
  <!-- Show on mobile only -->
  <div class="block md:hidden">Mobile only</div>
</template>
```

## Common Patterns

### Site Context Filtering

Use the selected site from props:

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

interface Props {
  buildings: Array<Building>
  selectedSiteId?: number
}

const props = defineProps<Props>()

const clearSiteFilter = () => {
  router.get('/buildings', { site_id: null })
}
</script>

<template>
  <div>
    <div v-if="selectedSiteId" class="mb-4">
      <Badge>Filtered by site</Badge>
      <Button @click="clearSiteFilter">Clear filter</Button>
    </div>
    
    <!-- Filtered buildings list -->
  </div>
</template>
```

### Loading States

```vue
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

const loading = ref(false)

router.on('start', () => { loading.value = true })
router.on('finish', () => { loading.value = false })
</script>

<template>
  <div>
    <div v-if="loading">Loading...</div>
    <div v-else>Content</div>
  </div>
</template>
```

### Error Handling

```vue
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const errors = computed(() => page.props.errors)
</script>

<template>
  <div>
    <div v-if="errors.code" class="text-red-500">
      {{ errors.code }}
    </div>
  </div>
</template>
```
