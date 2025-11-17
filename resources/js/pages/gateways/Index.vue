<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { router, Link, Head } from '@inertiajs/vue3'
import * as gateways from '@/actions/App/Http/Controllers/GatewayController'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
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
import { Plus, Search, Trash2, Eye, Pencil, Radio } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es'

interface Gateway {
  id: number
  serial_number: string
  mac_address: string | null
  ip_address: string | null
  site: { id: number; code: string }
  location: { id: number; code: string; description: string } | null
  status: string
  last_log_update: string | null
  created_at: string
}

interface Site {
  id: number
  code: string
}

interface Props {
  gateways: {
    data: Gateway[]
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  sites: Site[]
  filters: {
    search?: string
    site_id?: number
    status?: string
  }
}

const props = defineProps<Props>()

const search = ref(props.filters.search || '')
const siteId = ref(props.filters.site_id?.toString() || 'all')
const status = ref(props.filters.status || 'all')

const debouncedSearch = debounce(() => {
  router.get(
    gateways.index().url,
    {
      search: search.value,
      site_id: siteId.value && siteId.value !== 'all' ? siteId.value : undefined,
      status: status.value && status.value !== 'all' ? status.value : undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
    }
  )
}, 300)

watch([search, siteId, status], () => {
  debouncedSearch()
})

const getStatusColor = (status: string) => {
  return status === 'Online'
    ? 'bg-green-500'
    : status === 'Offline'
    ? 'bg-red-500'
    : 'bg-gray-500'
}

const deleteGateway = (gateway: Gateway) => {
  if (confirm(`Are you sure you want to delete gateway ${gateway.serial_number}?`)) {
    router.delete(gateways.destroy({ gateway: gateway.id }).url)
  }
}
</script>

<template>
  <Head title="Gateways" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
            <Radio class="h-8 w-8" />
            Gateways
          </h1>
          <p class="text-muted-foreground">
            Manage CAMR gateways and their configurations
          </p>
        </div>
        <Link :href="gateways.create().url">
          <Button>
            <Plus class="mr-2 h-4 w-4" />
            Add Gateway
          </Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>All Gateways</CardTitle>
              <CardDescription>
                {{ props.gateways.total }} total gateways
              </CardDescription>
            </div>
            <div class="flex gap-3">
              <!-- Site Filter -->
              <Select v-model="siteId">
                <SelectTrigger class="w-48">
                  <SelectValue placeholder="Filter by site" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Sites</SelectItem>
                  <SelectItem v-for="site in props.sites" :key="site.id" :value="site.id.toString()">
                    {{ site.code }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <!-- Status Filter -->
              <Select v-model="status">
                <SelectTrigger class="w-40">
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Status</SelectItem>
                  <SelectItem value="online">Online</SelectItem>
                  <SelectItem value="offline">Offline</SelectItem>
                </SelectContent>
              </Select>

              <!-- Search -->
              <div class="relative w-80">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  v-model="search"
                  placeholder="Search by serial, MAC, or IP..."
                  class="pl-10"
                />
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Serial Number</TableHead>
                <TableHead>Site</TableHead>
                <TableHead>MAC Address</TableHead>
                <TableHead>IP Address</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Last Update</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="props.gateways.data.length === 0">
                <TableCell colspan="7" class="text-center text-muted-foreground">
                  No gateways found
                </TableCell>
              </TableRow>
              <TableRow v-for="gateway in props.gateways.data" :key="gateway.id">
                <TableCell class="font-medium">{{ gateway.serial_number }}</TableCell>
                <TableCell>{{ gateway.site.code }}</TableCell>
                <TableCell>{{ gateway.mac_address || '—' }}</TableCell>
                <TableCell>{{ gateway.ip_address || '—' }}</TableCell>
                <TableCell>
                  <Badge :class="getStatusColor(gateway.status)" variant="outline">
                    {{ gateway.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  {{ gateway.last_log_update ? new Date(gateway.last_log_update).toLocaleString() : 'Never' }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button 
                      variant="ghost" 
                      size="sm"
                      @click="router.visit(gateways.show({ gateway: gateway.id }).url)"
                    >
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button 
                      variant="ghost" 
                      size="sm"
                      @click="router.visit(gateways.edit({ gateway: gateway.id }).url)"
                    >
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="deleteGateway(gateway)"
                    >
                      <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <!-- Pagination -->
          <div v-if="props.gateways.last_page > 1" class="mt-4 flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
              Showing {{ props.gateways.data.length }} of {{ props.gateways.total }} gateways
            </p>
            <div class="flex gap-2">
              <Button
                v-for="page in props.gateways.last_page"
                :key="page"
                :variant="page === props.gateways.current_page ? 'default' : 'outline'"
                size="sm"
                @click="router.get(gateways.index({ query: { page } }).url)"
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
