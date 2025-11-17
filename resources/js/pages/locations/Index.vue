<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import SortableTableHead from '@/components/SortableTableHead.vue'
import * as locations from '@/actions/App/Http/Controllers/LocationController'
import { Plus, Search, MapPin, Pencil, Trash2, Eye, Download } from 'lucide-vue-next'
import { useSortable } from '@/composables/useSortable'
import { useExport } from '@/composables/useExport'
import FilterPresets from '@/components/FilterPresets.vue'
import ColumnPreferences from '@/components/ColumnPreferences.vue'
import { useColumnPreferences } from '@/composables/useColumnPreferences'

interface Props {
  locations: {
    data: Array<{
      id: number
      code: string
      description: string
      site: { id: number; code: string }
      building: { id: number; code: string } | null
      created_at: string
    }>
    total: number
    last_page: number
    links: Array<{ url: string | null; label: string; active: boolean }>
  }
  sites: Array<{ id: number; code: string }>
  buildings: Array<{ id: number; code: string; site_id: number }>
  filters: { search?: string; site_id?: string; building_id?: string; sort?: string; direction?: 'asc' | 'desc' }
}

const props = defineProps<Props>()
const search = ref(props.filters.search || '')
const siteId = ref(props.filters.site_id || 'all')
const buildingId = ref(props.filters.building_id || 'all')

// Filter buildings based on selected site
const filteredBuildings = computed(() => {
  if (siteId.value === 'all') return props.buildings
  return props.buildings.filter(b => b.site_id === parseInt(siteId.value))
})

function applyFilters() {
  router.get(locations.index().url, {
    search: search.value || undefined,
    site_id: siteId.value !== 'all' ? siteId.value : undefined,
    building_id: buildingId.value !== 'all' ? buildingId.value : undefined,
  }, { preserveState: true, preserveScroll: true })
}

// Sorting
const sorting = useSortable(locations.index().url, {
  column: props.filters.sort || null,
  direction: props.filters.direction || 'asc',
})

function handleSort(column: string) {
  sorting.sort(column, {
    search: search.value || undefined,
    site_id: siteId.value !== 'all' ? siteId.value : undefined,
    building_id: buildingId.value !== 'all' ? buildingId.value : undefined,
  })
}

// Column preferences
const columnPrefs = useColumnPreferences({
  storageKey: 'locations-column-preferences',
  defaultColumns: [
    { key: 'code', label: 'Code', locked: true },
    { key: 'description', label: 'Description' },
    { key: 'site', label: 'Site' },
    { key: 'building', label: 'Building' },
    { key: 'created', label: 'Created' },
    { key: 'actions', label: 'Actions', locked: true },
  ],
})

// Export
const { exportToCSV } = useExport()

function exportLocations() {
  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'code', label: 'Code' },
    { key: 'description', label: 'Description' },
    { key: 'site.code', label: 'Site' },
    { key: 'building.code', label: 'Building' },
    { key: 'created_at', label: 'Created At' },
  ]
  
  const timestamp = new Date().toISOString().split('T')[0]
  exportToCSV(props.locations.data, `locations-${timestamp}`, columns)
}
</script>

<template>
  <Head title="Locations" />
  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
            <MapPin class="h-8 w-8" />
            Locations
          </h1>
          <p class="text-muted-foreground">Manage facility locations</p>
        </div>
        <Link :href="locations.create().url">
          <Button><Plus class="mr-2 h-4 w-4" />Add Location</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>All Locations</CardTitle>
              <CardDescription>{{ props.locations.total }} total locations</CardDescription>
            </div>
            <div class="flex gap-3">
              <Select v-model="siteId" @update:model-value="applyFilters">
                <SelectTrigger class="w-40"><SelectValue placeholder="Site" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Sites</SelectItem>
                  <SelectItem v-for="site in props.sites" :key="site.id" :value="site.id.toString()">{{ site.code }}</SelectItem>
                </SelectContent>
              </Select>
              <Select v-model="buildingId" @update:model-value="applyFilters">
                <SelectTrigger class="w-40"><SelectValue placeholder="Building" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Buildings</SelectItem>
                  <SelectItem v-for="building in filteredBuildings" :key="building.id" :value="building.id.toString()">{{ building.code }}</SelectItem>
                </SelectContent>
              </Select>
              <div class="relative w-80">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search locations..." class="pl-10" @keyup.enter="applyFilters" />
              </div>
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between">
            <Button variant="outline" size="sm" @click="exportLocations">
              <Download class="h-4 w-4 mr-2" />
              Export CSV
            </Button>
            <div class="flex items-center gap-2">
              <FilterPresets
                storage-key="locations-filter-presets"
                :route-url="locations.index().url"
                :current-filters="props.filters"
              />
              <ColumnPreferences
                storage-key="locations-column-preferences"
                :default-columns="[
                  { key: 'code', label: 'Code', locked: true },
                  { key: 'description', label: 'Description' },
                  { key: 'site', label: 'Site' },
                  { key: 'building', label: 'Building' },
                  { key: 'created', label: 'Created' },
                  { key: 'actions', label: 'Actions', locked: true },
                ]"
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <SortableTableHead column="code" :sort-column="sorting.sortColumn.value" :sort-direction="sorting.sortDirection.value" @sort="handleSort">Code</SortableTableHead>
                <SortableTableHead v-if="columnPrefs.isColumnVisible('description')" column="description" :sort-column="sorting.sortColumn.value" :sort-direction="sorting.sortDirection.value" @sort="handleSort">Description</SortableTableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('site')">Site</TableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('building')">Building</TableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('created')">Created</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="props.locations.data.length === 0">
                <TableCell colspan="6" class="text-center text-muted-foreground">No locations found</TableCell>
              </TableRow>
              <TableRow v-for="location in props.locations.data" :key="location.id">
                <TableCell class="font-medium">{{ location.code }}</TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('description')">{{ location.description }}</TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('site')"><Badge variant="outline">{{ location.site.code }}</Badge></TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('building')">{{ location.building?.code || '—' }}</TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('created')">{{ new Date(location.created_at).toLocaleDateString() }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" @click="router.visit(locations.show({ location: location.id }).url)">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="router.visit(locations.edit({ location: location.id }).url)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="router.delete(locations.destroy({ location: location.id }).url)">
                      <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <div v-if="props.locations.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
            <Button v-for="link in props.locations.links" :key="link.label" :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" @click="link.url && router.visit(link.url)" v-html="link.label" />
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
