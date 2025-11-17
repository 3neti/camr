<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import SortableTableHead from '@/components/SortableTableHead.vue'
import * as configFiles from '@/actions/App/Http/Controllers/ConfigurationFileController'
import { Plus, Search, FileCode, Pencil, Trash2, Eye, Download } from 'lucide-vue-next'
import { useSortable } from '@/composables/useSortable'
import { useExport } from '@/composables/useExport'
import FilterPresets from '@/components/FilterPresets.vue'
import ColumnPreferences from '@/components/ColumnPreferences.vue'
import { useColumnPreferences } from '@/composables/useColumnPreferences'
import { getTableConfig } from '@/config/tableColumnsLoader'

interface Props {
  configFiles: {
    data: Array<{
      id: number
      meter_model: string
      meters_count: number
      created_at: string
    }>
    total: number
    last_page: number
    links: Array<{ url: string | null; label: string; active: boolean }>
  }
  filters: { search?: string; sort?: string; direction?: 'asc' | 'desc' }
}

const props = defineProps<Props>()
const search = ref(props.filters.search || '')

function applyFilters() {
  router.get(configFiles.index().url, {
    search: search.value || undefined,
  }, { preserveState: true, preserveScroll: true })
}

// Sorting
const sorting = useSortable(configFiles.index().url, {
  column: props.filters.sort || null,
  direction: props.filters.direction || 'asc',
})

function handleSort(column: string) {
  sorting.sort(column, { search: search.value || undefined })
}

// Column preferences
const tableConfig = getTableConfig('configFiles')!
const columnPrefs = useColumnPreferences({
  storageKey: tableConfig.storageKey,
  defaultColumns: tableConfig.columns,
})

// Export
const { exportToCSV } = useExport()

function exportConfigFiles() {
  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'meter_model', label: 'Meter Model' },
    { key: 'meters_count', label: 'Meters Count' },
    { key: 'created_at', label: 'Created At' },
  ]
  
  const timestamp = new Date().toISOString().split('T')[0]
  exportToCSV(props.configFiles.data, `config-files-${timestamp}`, columns)
}
</script>

<template>
  <Head title="Configuration Files" />
  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
            <FileCode class="h-8 w-8" />
            Configuration Files
          </h1>
          <p class="text-muted-foreground">Manage meter configuration files</p>
        </div>
        <Link :href="configFiles.create().url">
          <Button><Plus class="mr-2 h-4 w-4" />Add Config File</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>All Configuration Files</CardTitle>
              <CardDescription>{{ props.configFiles.total }} total config files</CardDescription>
            </div>
            <div class="relative w-80">
              <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input v-model="search" placeholder="Search meter models..." class="pl-10" @keyup.enter="applyFilters" />
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between">
            <Button variant="outline" size="sm" @click="exportConfigFiles">
              <Download class="h-4 w-4 mr-2" />
              Export CSV
            </Button>
            <div class="flex items-center gap-2">
              <FilterPresets
                storage-key="config-files-filter-presets"
                :route-url="configFiles.index().url"
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
                <SortableTableHead column="meter_model" :sort-column="sorting.sortColumn.value" :sort-direction="sorting.sortDirection.value" @sort="handleSort">Meter Model</SortableTableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('meters_using')">Meters Using</TableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('created')">Created</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="props.configFiles.data.length === 0">
                <TableCell colspan="4" class="text-center text-muted-foreground">No configuration files found</TableCell>
              </TableRow>
              <TableRow v-for="config in props.configFiles.data" :key="config.id">
                <TableCell class="font-medium">{{ config.meter_model }}</TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('meters_using')">
                  <Badge variant="secondary">{{ config.meters_count }} meters</Badge>
                </TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('created')">{{ new Date(config.created_at).toLocaleDateString() }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" @click="router.visit(configFiles.show({ config_file: config.id }).url)">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="router.visit(configFiles.edit({ config_file: config.id }).url)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="router.delete(configFiles.destroy({ config_file: config.id }).url)">
                      <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <div v-if="props.configFiles.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
            <Button v-for="link in props.configFiles.links" :key="link.label" :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" @click="link.url && router.visit(link.url)" v-html="link.label" />
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
