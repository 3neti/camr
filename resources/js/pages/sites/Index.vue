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
import { Plus, Search, Trash2, Eye, Pencil } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es'
import { useBulkActions } from '@/composables/useBulkActions'
import { useSortable } from '@/composables/useSortable'

interface Site {
  id: number
  code: string
  company: { id: number; name: string }
  division: { id: number; name: string }
  status: string
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

const getStatusColor = (status: string) => {
  return status === 'Online'
    ? 'bg-green-500'
    : status === 'Offline'
    ? 'bg-red-500'
    : 'bg-gray-500'
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
          <div v-if="bulk.hasSelection.value" class="mt-4 flex items-center gap-2">
            <Button variant="destructive" size="sm" @click="bulkDeleteSites">
              <Trash2 class="h-4 w-4 mr-2" />
              Delete Selected ({{ bulk.selectedIds.value.length }})
            </Button>
            <Button variant="outline" size="sm" @click="bulk.clearSelection()">
              Clear Selection
            </Button>
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
                <TableHead>Company</TableHead>
                <TableHead>Division</TableHead>
                <SortableTableHead column="status" :sort-column="sorting.sortColumn.value" :sort-direction="sorting.sortDirection.value" @sort="handleSort">Status</SortableTableHead>
                <TableHead>Last Update</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="props.sites.data.length === 0">
                <TableCell colspan="7" class="text-center text-muted-foreground">
                  No sites found
                </TableCell>
              </TableRow>
              <TableRow v-for="site in props.sites.data" :key="site.id">
                <TableCell>
                  <Checkbox 
                    :checked="bulk.isSelected(site.id)" 
                    @update:checked="bulk.toggleSelection(site.id)" 
                  />
                </TableCell>
                <TableCell class="font-medium">{{ site.code }}</TableCell>
                <TableCell>{{ site.company.name }}</TableCell>
                <TableCell>{{ site.division.name }}</TableCell>
                <TableCell>
                  <Badge :class="getStatusColor(site.status)" variant="outline">
                    {{ site.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  {{ site.last_log_update ? new Date(site.last_log_update).toLocaleString() : 'Never' }}
                </TableCell>
                <TableCell class="text-right">
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
