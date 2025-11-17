<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { router, Link, Head } from '@inertiajs/vue3'
import * as meters from '@/actions/App/Http/Controllers/MeterController'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Plus, Search, Trash2, Eye, Pencil, Zap } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es'
import { useBulkActions } from '@/composables/useBulkActions'

interface Meter {
  id: number
  name: string
  type: string
  brand: string
  customer_name: string
  status: string
  status_label: string
  last_log_update: string | null
  gateway: { id: number; serial_number: string; site: { code: string } }
  location: { code: string } | null
}

interface Props {
  meters: {
    data: Meter[]
    current_page: number
    last_page: number
    total: number
  }
  gateways: Array<{ id: number; serial_number: string }>
  sites: Array<{ id: number; code: string }>
  filters: {
    search?: string
    gateway_id?: number
    site_id?: number
    status?: string
  }
}

const props = defineProps<Props>()

const search = ref(props.filters.search || '')
const gatewayId = ref(props.filters.gateway_id?.toString() || 'all')
const siteId = ref(props.filters.site_id?.toString() || 'all')
const status = ref(props.filters.status || 'all')

const debouncedSearch = debounce(() => {
  router.get(
    meters.index().url,
    {
      search: search.value,
      gateway_id: gatewayId.value !== 'all' ? gatewayId.value : undefined,
      site_id: siteId.value !== 'all' ? siteId.value : undefined,
      status: status.value !== 'all' ? status.value : undefined,
    },
    { preserveState: true, preserveScroll: true }
  )
}, 300)

watch([search, gatewayId, siteId, status], debouncedSearch)

const getStatusColor = (statusLabel: string) => {
  return statusLabel === 'Online' ? 'bg-green-500' : statusLabel === 'Offline' ? 'bg-red-500' : 'bg-gray-500'
}

const deleteMeter = (meter: Meter) => {
  if (confirm(`Delete meter ${meter.name}?`)) {
    router.delete(meters.destroy({ meter: meter.id }).url)
  }
}

// Bulk actions
const bulk = useBulkActions(props.meters.data)

function bulkDeleteMeters() {
  if (bulk.selectedIds.value.length === 0) return
  
  if (confirm(`Are you sure you want to delete ${bulk.selectedIds.value.length} meters?`)) {
    router.post('/meters/bulk-delete', { ids: bulk.selectedIds.value }, {
      onSuccess: () => bulk.clearSelection()
    })
  }
}
</script>

<template>
  <Head title="Meters" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
            <Zap class="h-8 w-8" />
            Meters
          </h1>
          <p class="text-muted-foreground">Manage CAMR meters and readings</p>
        </div>
        <Link :href="meters.create().url">
          <Button><Plus class="mr-2 h-4 w-4" />Add Meter</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>All Meters</CardTitle>
              <CardDescription>
                {{ props.meters.total }} total meters
                <span v-if="bulk.hasSelection.value" class="ml-2">
                  · <strong>{{ bulk.selectedIds.value.length }}</strong> selected
                </span>
              </CardDescription>
            </div>
            <div class="flex gap-3">
              <Select v-model="siteId">
                <SelectTrigger class="w-40"><SelectValue placeholder="Site" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Sites</SelectItem>
                  <SelectItem v-for="site in props.sites" :key="site.id" :value="site.id.toString()">{{ site.code }}</SelectItem>
                </SelectContent>
              </Select>

              <Select v-model="gatewayId">
                <SelectTrigger class="w-48"><SelectValue placeholder="Gateway" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Gateways</SelectItem>
                  <SelectItem v-for="gw in props.gateways" :key="gw.id" :value="gw.id.toString()">{{ gw.serial_number }}</SelectItem>
                </SelectContent>
              </Select>

              <Select v-model="status">
                <SelectTrigger class="w-40"><SelectValue placeholder="Status" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Status</SelectItem>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                </SelectContent>
              </Select>

              <div class="relative w-80">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search meters..." class="pl-10" />
              </div>
            </div>
          </div>
          <div v-if="bulk.hasSelection.value" class="mt-4 flex items-center gap-2">
            <Button variant="destructive" size="sm" @click="bulkDeleteMeters">
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
                <TableHead>Name</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Brand</TableHead>
                <TableHead>Customer</TableHead>
                <TableHead>Site</TableHead>
                <TableHead>Gateway</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="props.meters.data.length === 0">
                <TableCell colspan="9" class="text-center text-muted-foreground">No meters found</TableCell>
              </TableRow>
              <TableRow v-for="meter in props.meters.data" :key="meter.id">
                <TableCell>
                  <Checkbox 
                    :checked="bulk.isSelected(meter.id)" 
                    @update:checked="bulk.toggleSelection(meter.id)" 
                  />
                </TableCell>
                <TableCell class="font-medium">{{ meter.name }}</TableCell>
                <TableCell>{{ meter.type }}</TableCell>
                <TableCell>{{ meter.brand }}</TableCell>
                <TableCell>{{ meter.customer_name }}</TableCell>
                <TableCell>{{ meter.gateway.site.code }}</TableCell>
                <TableCell>{{ meter.gateway.serial_number }}</TableCell>
                <TableCell>
                  <Badge :class="getStatusColor(meter.status_label)" variant="outline">{{ meter.status_label }}</Badge>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" @click="router.visit(meters.show({ meter: meter.id }).url)">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="router.visit(meters.edit({ meter: meter.id }).url)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="deleteMeter(meter)">
                      <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <div v-if="props.meters.last_page > 1" class="mt-4 flex items-center justify-between">
            <p class="text-sm text-muted-foreground">Showing {{ props.meters.data.length }} of {{ props.meters.total }} meters</p>
            <div class="flex gap-2">
              <Button v-for="page in props.meters.last_page" :key="page" :variant="page === props.meters.current_page ? 'default' : 'outline'" size="sm" @click="router.get(meters.index({ query: { page } }).url)">{{ page }}</Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
