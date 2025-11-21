<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import SortableTableHead from '@/components/SortableTableHead.vue'
import * as buildings from '@/actions/App/Http/Controllers/BuildingController'
import { Plus, Search, Building2, Pencil, Trash2, Eye, Download } from 'lucide-vue-next'
import { useSortable } from '@/composables/useSortable'
import { useExport } from '@/composables/useExport'
import { useSiteContext } from '@/composables/useSiteContext'
import FilterPresets from '@/components/FilterPresets.vue'
import ColumnPreferences from '@/components/ColumnPreferences.vue'
import { useColumnPreferences } from '@/composables/useColumnPreferences'
import { getTableConfig } from '@/config/tableColumnsLoader'

interface Props {
  buildings: {
    data: Array<{
      id: number
      code: string
      description: string | null
      site: { id: number; code: string }
      created_at: string
    }>
    total: number
    last_page: number
    links: Array<{ url: string | null; label: string; active: boolean }>
  }
  sites: Array<{ id: number; code: string }>
  filters: { search?: string; site_id?: string; sort?: string; direction?: 'asc' | 'desc' }
}

const props = defineProps<Props>()

// Site context - use session-stored site if available
const { selectedSiteId } = useSiteContext()

const search = ref(props.filters.search || '')
// Initialize from session context, fallback to filter, then 'all'
const siteId = ref(
  (selectedSiteId.value?.toString()) || 
  (props.filters.site_id) || 
  'all'
)

function applyFilters() {
  router.get(buildings.index().url, {
    search: search.value || undefined,
    // Pass 'all' to clear session, or the actual site_id to set it
    site_id: siteId.value === 'all' ? 'all' : siteId.value,
  }, { preserveState: true, preserveScroll: true })
}

// Sorting
const sorting = useSortable(buildings.index().url, {
  column: props.filters.sort || null,
  direction: props.filters.direction || 'asc',
})

function handleSort(column: string) {
  sorting.sort(column, {
    search: search.value || undefined,
    site_id: siteId.value !== 'all' ? siteId.value : undefined,
  })
}

// Column preferences
const tableConfig = getTableConfig('buildings')!
const columnPrefs = useColumnPreferences({
  storageKey: tableConfig.storageKey,
  defaultColumns: tableConfig.columns,
})

// Export
const { exportToCSV } = useExport()

function exportBuildings() {
  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'code', label: 'Code' },
    { key: 'description', label: 'Description' },
    { key: 'site.code', label: 'Site' },
    { key: 'created_at', label: 'Created At' },
  ]
  
  const timestamp = new Date().toISOString().split('T')[0]
  exportToCSV(props.buildings.data, `buildings-${timestamp}`, columns)
}
</script>

<template>
  <Head title="Buildings" />
  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
            <Building2 class="h-8 w-8" />
            Buildings
          </h1>
          <p class="text-muted-foreground">Manage site buildings and facilities</p>
        </div>
        <Link :href="buildings.create().url">
          <Button><Plus class="mr-2 h-4 w-4" />Add Building</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>All Buildings</CardTitle>
              <CardDescription>{{ props.buildings.total }} total buildings</CardDescription>
            </div>
            <div class="flex gap-3">
              <Select v-model="siteId" @update:model-value="applyFilters">
                <SelectTrigger class="w-40"><SelectValue placeholder="Site" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Sites</SelectItem>
                  <SelectItem v-for="site in props.sites" :key="site.id" :value="site.id.toString()">{{ site.code }}</SelectItem>
                </SelectContent>
              </Select>
              <div class="relative w-80">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search buildings..." class="pl-10" @keyup.enter="applyFilters" />
              </div>
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between">
            <Button variant="outline" size="sm" @click="exportBuildings">
              <Download class="h-4 w-4 mr-2" />
              Export CSV
            </Button>
            <div class="flex items-center gap-2">
              <FilterPresets
                storage-key="buildings-filter-presets"
                :route-url="buildings.index().url"
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
                <SortableTableHead column="code" :sort-column="sorting.sortColumn" :sort-direction="sorting.sortDirection" @sort="handleSort">Code</SortableTableHead>
                <SortableTableHead v-if="columnPrefs.isColumnVisible('description')" column="description" :sort-column="sorting.sortColumn" :sort-direction="sorting.sortDirection" @sort="handleSort">Description</SortableTableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('site')">Site</TableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('created')">Created</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="props.buildings.data.length === 0">
                <TableCell colspan="5" class="text-center text-muted-foreground">No buildings found</TableCell>
              </TableRow>
              <TableRow v-for="building in props.buildings.data" :key="building.id">
                <TableCell class="font-medium">{{ building.code }}</TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('description')">{{ building.description || '—' }}</TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('site')"><Badge variant="outline">{{ building.site.code }}</Badge></TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('created')">{{ new Date(building.created_at).toLocaleDateString() }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" @click="router.visit(buildings.show({ building: building.id }).url)">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="router.visit(buildings.edit({ building: building.id }).url)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="router.delete(buildings.destroy({ building: building.id }).url)">
                      <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <div v-if="props.buildings.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
            <Button v-for="link in props.buildings.links" :key="link.label" :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" @click="link.url && router.visit(link.url)" v-html="link.label" />
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
