<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import * as sites from '@/actions/App/Http/Controllers/SiteController'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { ArrowLeft, Pencil, Building, Radio, Zap, MapPin } from 'lucide-vue-next'

interface Site {
  id: number
  code: string
  status: string
  last_log_update: string | null
  company: { name: string; code: string }
  division: { name: string; code: string }
  buildings: Array<{ id: number; code: string; description: string }>
  gateways: Array<{
    id: number
    serial_number: string
    mac_address: string
    ip_address: string
    status: string
    last_log_update: string | null
    location?: { code: string; description: string }
  }>
  meters: Array<{
    id: number
    name: string
    type: string
    brand: string
    status: string
    customer_name: string
    last_log_update: string | null
    gateway: { serial_number: string }
    location: { code: string }
  }>
}

interface Props {
  site: Site
}

const props = defineProps<Props>()

const getStatusColor = (status: string) => {
  return status === 'Online'
    ? 'bg-green-500'
    : status === 'Offline'
    ? 'bg-red-500'
    : 'bg-gray-500'
}

const onlineGateways = props.site.gateways.filter((g) => g.status === 'Online').length
const offlineGateways = props.site.gateways.filter((g) => g.status === 'Offline' || g.status === 'No Data').length
const onlineMeters = props.site.meters.filter((m) => m.status === 'Active' && m.last_log_update).length
const offlineMeters = props.site.meters.filter((m) => !m.last_log_update || m.status === 'Inactive').length
</script>

<template>
  <Head :title="`Site: ${site.code}`" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="sites.index().url">
            <Button variant="ghost" size="sm">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Sites
            </Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
              {{ site.code }}
              <Badge :class="getStatusColor(site.status)" variant="outline">
                {{ site.status }}
              </Badge>
            </h1>
            <p class="text-muted-foreground">
              {{ site.company.name }} · {{ site.division.name }}
            </p>
          </div>
        </div>
        <Link :href="sites.edit({ site: site.id }).url">
          <Button>
            <Pencil class="h-4 w-4 mr-2" />
            Edit Site
          </Button>
        </Link>
      </div>

      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Gateways</CardTitle>
            <Radio class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ site.gateways.length }}</div>
            <p class="text-xs text-muted-foreground">
              {{ onlineGateways }} online, {{ offlineGateways }} offline
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Meters</CardTitle>
            <Zap class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ site.meters.length }}</div>
            <p class="text-xs text-muted-foreground">
              {{ onlineMeters }} reporting, {{ offlineMeters }} silent
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Buildings</CardTitle>
            <Building class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ site.buildings.length }}</div>
            <p class="text-xs text-muted-foreground">
              Physical locations
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Last Update</CardTitle>
            <MapPin class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ site.last_log_update ? new Date(site.last_log_update).toLocaleDateString() : 'Never' }}
            </div>
            <p class="text-xs text-muted-foreground">
              {{ site.last_log_update ? new Date(site.last_log_update).toLocaleTimeString() : '' }}
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Tabs -->
      <Tabs default-value="status" class="space-y-4">
        <TabsList>
          <TabsTrigger value="status">Status</TabsTrigger>
          <TabsTrigger value="gateways">Gateways</TabsTrigger>
          <TabsTrigger value="meters">Meters</TabsTrigger>
          <TabsTrigger value="buildings">Buildings</TabsTrigger>
        </TabsList>

        <!-- Status Tab -->
        <TabsContent value="status" class="space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Site Information</CardTitle>
              </CardHeader>
              <CardContent class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Code:</span>
                  <span class="font-medium">{{ site.code }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Company:</span>
                  <span class="font-medium">{{ site.company.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Division:</span>
                  <span class="font-medium">{{ site.division.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Status:</span>
                  <Badge :class="getStatusColor(site.status)" variant="outline">
                    {{ site.status }}
                  </Badge>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Quick Stats</CardTitle>
              </CardHeader>
              <CardContent class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Total Gateways:</span>
                  <span class="font-medium">{{ site.gateways.length }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Total Meters:</span>
                  <span class="font-medium">{{ site.meters.length }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Buildings:</span>
                  <span class="font-medium">{{ site.buildings.length }}</span>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <!-- Gateways Tab -->
        <TabsContent value="gateways">
          <Card>
            <CardHeader>
              <CardTitle>Gateways</CardTitle>
              <CardDescription>
                RTU devices collecting meter data
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Serial Number</TableHead>
                    <TableHead>MAC Address</TableHead>
                    <TableHead>IP Address</TableHead>
                    <TableHead>Location</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Last Update</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="site.gateways.length === 0">
                    <TableCell colspan="6" class="text-center text-muted-foreground">
                      No gateways configured
                    </TableCell>
                  </TableRow>
                  <TableRow v-for="gateway in site.gateways" :key="gateway.id">
                    <TableCell class="font-medium">{{ gateway.serial_number }}</TableCell>
                    <TableCell>{{ gateway.mac_address }}</TableCell>
                    <TableCell>{{ gateway.ip_address }}</TableCell>
                    <TableCell>
                      {{ gateway.location ? `${gateway.location.code} - ${gateway.location.description}` : 'N/A' }}
                    </TableCell>
                    <TableCell>
                      <Badge :class="getStatusColor(gateway.status)" variant="outline">
                        {{ gateway.status }}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {{ gateway.last_log_update ? new Date(gateway.last_log_update).toLocaleString() : 'Never' }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Meters Tab -->
        <TabsContent value="meters">
          <Card>
            <CardHeader>
              <CardTitle>Meters</CardTitle>
              <CardDescription>
                Electricity meters monitored at this site
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Meter Name</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Brand</TableHead>
                    <TableHead>Customer</TableHead>
                    <TableHead>Gateway</TableHead>
                    <TableHead>Location</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="site.meters.length === 0">
                    <TableCell colspan="7" class="text-center text-muted-foreground">
                      No meters configured
                    </TableCell>
                  </TableRow>
                  <TableRow v-for="meter in site.meters" :key="meter.id">
                    <TableCell class="font-medium">{{ meter.name }}</TableCell>
                    <TableCell>{{ meter.type }}</TableCell>
                    <TableCell>{{ meter.brand }}</TableCell>
                    <TableCell>{{ meter.customer_name || 'N/A' }}</TableCell>
                    <TableCell>{{ meter.gateway.serial_number }}</TableCell>
                    <TableCell>{{ meter.location.code }}</TableCell>
                    <TableCell>
                      <Badge variant="outline">{{ meter.status }}</Badge>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Buildings Tab -->
        <TabsContent value="buildings">
          <Card>
            <CardHeader>
              <CardTitle>Buildings</CardTitle>
              <CardDescription>
                Physical buildings at this site
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Code</TableHead>
                    <TableHead>Description</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="site.buildings.length === 0">
                    <TableCell colspan="2" class="text-center text-muted-foreground">
                      No buildings configured
                    </TableCell>
                  </TableRow>
                  <TableRow v-for="building in site.buildings" :key="building.id">
                    <TableCell class="font-medium">{{ building.code }}</TableCell>
                    <TableCell>{{ building.description }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
