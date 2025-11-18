<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { router, Link, Head } from '@inertiajs/vue3'
import * as sites from '@/actions/App/Http/Controllers/SiteController'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import SortableTableHead from '@/components/SortableTableHead.vue'
import { Plus, Search, Trash2, Eye, Pencil, Download, Building2, MapPin, Radio, Zap } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es'
import { useBulkActions } from '@/composables/useBulkActions'
import { useSortable } from '@/composables/useSortable'
import { useExport } from '@/composables/useExport'
import FilterPresets from '@/components/FilterPresets.vue'
import ColumnPreferences from '@/components/ColumnPreferences.vue'
import { useColumnPreferences } from '@/composables/useColumnPreferences'
import { getTableConfig } from '@/config/tableColumnsLoader'

interface Site {
  id: number
  code: string
  company: { id: number; name: string }
  division: { id: number; name: string }
  status: boolean
  status_label: string
  last_log_update: string | null
  created_at: string
}

interface Props {
  sites: {
    data: Site[]
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  filters: {
    search?: string
    sort?: string
    direction?: 'asc' | 'desc'
  }
}

const props = defineProps<Props>()

const search = ref(props.filters.search || '')

const debouncedSearch = debounce(() => {
  router.get(
    sites.index().url,
    { search: search.value },
    {
      preserveState: true,
      preserveScroll: true,
    }
  )
}, 300)

watch(search, () => {
  debouncedSearch()
})

const getStatusColor = (status: boolean) => {
  return status 
    ? 'bg-green-500/10 text-green-700 border-green-500/20 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/30' 
    : 'bg-red-500/10 text-red-700 border-red-500/20 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/30'
}

const deleteSite = (site: Site) => {
  if (confirm(`Are you sure you want to delete site ${site.code}?`)) {
    router.delete(sites.destroy({ site: site.id }).url)
  }
}

// Bulk actions
const bulk = useBulkActions(props.sites.data)

function bulkDeleteSites() {
  if (bulk.selectedIds.value.length === 0) return
  
  if (confirm(`Are you sure you want to delete ${bulk.selectedIds.value.length} sites?`)) {
    router.post('/sites/bulk-delete', { ids: bulk.selectedIds.value }, {
      onSuccess: () => bulk.clearSelection()
    })
  }
}

// Sorting
const sorting = useSortable(sites.index().url, {
  column: props.filters.sort || null,
  direction: props.filters.direction || 'asc',
})

function handleSort(column: string) {
  sorting.sort(column, { search: search.value || undefined })
}

// Handle row click to toggle selection
function handleRowClick(event: MouseEvent, siteId: number) {
  // Don't toggle if clicking on interactive elements (handled by @click.stop on those cells)
  bulk.toggleSelection(siteId)
}

// Save selected site IDs to sessionStorage for context filtering
function saveSelectedContext() {
  if (bulk.selectedIds.value.length > 0) {
    sessionStorage.setItem('selectedSiteIds', JSON.stringify(bulk.selectedIds.value))
  }
}

// Navigate to related pages with selected sites as filter
function goToBuildings() {
  saveSelectedContext()
  const siteId = bulk.selectedIds.value.length === 1 ? bulk.selectedIds.value[0] : undefined
  router.get('/buildings', siteId ? { site_id: siteId } : {})
}

function goToLocations() {
  saveSelectedContext()
  const siteId = bulk.selectedIds.value.length === 1 ? bulk.selectedIds.value[0] : undefined
  router.get('/locations', siteId ? { site_id: siteId } : {})
}

function goToGateways() {
  saveSelectedContext()
  const siteId = bulk.selectedIds.value.length === 1 ? bulk.selectedIds.value[0] : undefined
  router.get('/gateways', siteId ? { site_id: siteId } : {})
}

function goToMeters() {
  saveSelectedContext()
  const siteId = bulk.selectedIds.value.length === 1 ? bulk.selectedIds.value[0] : undefined
  router.get('/meters', siteId ? { site_id: siteId } : {})
}

// Column preferences
const tableConfig = getTableConfig('sites')!
const columnPrefs = useColumnPreferences({
  storageKey: tableConfig.storageKey,
  defaultColumns: tableConfig.columns,
})

// Export
const { exportToCSV } = useExport()

function exportSites() {
  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'code', label: 'Code' },
    { key: 'company.name', label: 'Company' },
    { key: 'division.name', label: 'Division' },
    { key: 'status_label', label: 'Status' },
    { key: 'last_log_update', label: 'Last Update' },
    { key: 'created_at', label: 'Created At' },
  ]
  
  const timestamp = new Date().toISOString().split('T')[0]
  exportToCSV(props.sites.data, `sites-${timestamp}`, columns)
}
</script>

<template>
  <Head title="Sites" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Sites</h1>
          <p class="text-muted-foreground">
            Manage CAMR sites, gateways, and meters
          </p>
        </div>
        <Link :href="sites.create().url">
          <Button>
            <Plus class="mr-2 h-4 w-4" />
            Add Site
          </Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>All Sites</CardTitle>
              <CardDescription>
                {{ props.sites.total }} total sites
                <span v-if="bulk.hasSelection.value" class="ml-2">
                  · <strong>{{ bulk.selectedIds.value.length }}</strong> selected
                </span>
              </CardDescription>
            </div>
            <div class="relative w-80">
              <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                v-model="search"
                placeholder="Search sites by code..."
                class="pl-10"
              />
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <template v-if="bulk.hasSelection.value">
                <div class="flex items-center gap-2 px-3 py-1 bg-primary/10 rounded-md">
                  <span class="text-sm font-medium">Selected ({{ bulk.selectedIds.value.length }})</span>
                  <div class="h-4 w-px bg-border" />
                  <Button variant="ghost" size="sm" @click="goToBuildings" title="View Buildings">
                    <Building2 class="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="sm" @click="goToLocations" title="View Locations">
                    <MapPin class="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="sm" @click="goToGateways" title="View Gateways">
                    <Radio class="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="sm" @click="goToMeters" title="View Meters">
                    <Zap class="h-4 w-4" />
                  </Button>
                  <div class="h-4 w-px bg-border" />
                  <Button variant="ghost" size="sm" @click="bulkDeleteSites" class="text-destructive hover:text-destructive" title="Delete Selected">
                    <Trash2 class="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="sm" @click="bulk.clearSelection()" title="Clear Selection">
                    <span class="text-xs">Clear</span>
                  </Button>
                </div>
              </template>
              <Button variant="outline" size="sm" @click="exportSites">
                <Download class="h-4 w-4 mr-2" />
                Export CSV
              </Button>
            </div>
            <div class="flex items-center gap-2">
              <FilterPresets
                storage-key="sites-filter-presets"
                :route-url="sites.index().url"
                :current-filters="props.filters"
              />
              <ColumnPreferences
                :storage-key="tableConfig.storageKey"
                :default-columns="tableConfig.columns"
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="w-12">
                  <Checkbox 
                    :checked="bulk.allSelected.value" 
                    :indeterminate="bulk.someSelected.value"
                    @update:checked="bulk.allSelected.value = $event" 
                  />
                </TableHead>
                <SortableTableHead column="code" :sort-column="sorting.sortColumn.value" :sort-direction="sorting.sortDirection.value" @sort="handleSort">Code</SortableTableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('company')">Company</TableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('division')">Division</TableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('status')">Status</TableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('last_update')">Last Update</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="props.sites.data.length === 0">
                <TableCell colspan="7" class="text-center text-muted-foreground">
                  No sites found
                </TableCell>
              </TableRow>
              <TableRow 
                v-for="site in props.sites.data" 
                :key="site.id"
                :class="{
                  'bg-muted/50 hover:bg-muted/70': bulk.isSelected(site.id),
                  'hover:bg-muted/30': !bulk.isSelected(site.id),
                  'cursor-pointer': true
                }"
                @click="handleRowClick($event, site.id)"
              >
                <TableCell @click.stop>
                  <Checkbox 
                    :checked="bulk.isSelected(site.id)" 
                    @update:checked="bulk.toggleSelection(site.id)" 
                  />
                </TableCell>
                <TableCell class="font-medium">{{ site.code }}</TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('company')">{{ site.company.name }}</TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('division')">{{ site.division.name }}</TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('status')">
                  <Badge :class="getStatusColor(site.status)">
                    {{ site.status_label }}
                  </Badge>
                </TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('last_update')">
                  {{ site.last_log_update ? new Date(site.last_log_update).toLocaleString() : 'Never' }}
                </TableCell>
                <TableCell class="text-right" @click.stop>
                  <div class="flex justify-end gap-2">
                    <Link :href="sites.show({ site: site.id }).url">
                      <Button variant="ghost" size="sm">
                        <Eye class="h-4 w-4" />
                      </Button>
                    </Link>
                    <Link :href="sites.edit({ site: site.id }).url">
                      <Button variant="ghost" size="sm">
                        <Pencil class="h-4 w-4" />
                      </Button>
                    </Link>
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="deleteSite(site)"
                    >
                      <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <!-- Pagination -->
          <div v-if="props.sites.last_page > 1" class="mt-4 flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
              Showing {{ props.sites.data.length }} of {{ props.sites.total }} sites
            </p>
            <div class="flex gap-2">
              <Button
                v-for="page in props.sites.last_page"
                :key="page"
                :variant="page === props.sites.current_page ? 'default' : 'outline'"
                size="sm"
                @click="router.get(sites.index({ query: { page } }).url)"
              >
                {{ page }}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
