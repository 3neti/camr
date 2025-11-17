<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import * as gateways from '@/actions/App/Http/Controllers/GatewayController'
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
import { ArrowLeft, Pencil, Radio, Zap, CheckCircle, XCircle } from 'lucide-vue-next'

interface Gateway {
  id: number
  serial_number: string
  mac_address: string | null
  ip_address: string | null
  ip_netmask: string | null
  ip_gateway: string | null
  server_ip: string | null
  connection_type: string | null
  status: string
  last_log_update: string | null
  software_version: string | null
  description: string | null
  update_csv: boolean
  update_site_code: boolean
  ssh_enabled: boolean
  force_load_profile: boolean
  idf_number: string | null
  switch_name: string | null
  idf_port: string | null
  site: { id: number; code: string }
  location: { id: number; code: string; description: string } | null
  meters: Array<{
    id: number
    name: string
    type: string
    brand: string
    status: string
    customer_name: string
    last_log_update: string | null
    location: { code: string }
  }>
}

interface Props {
  gateway: Gateway
}

const props = defineProps<Props>()

const getStatusColor = (status: string) => {
  return status === 'Online'
    ? 'bg-green-500'
    : status === 'Offline'
    ? 'bg-red-500'
    : 'bg-gray-500'
}

const activeMeters = props.gateway.meters.filter((m) => m.status === 'Active' && m.last_log_update).length
const inactiveMeters = props.gateway.meters.filter((m) => !m.last_log_update || m.status === 'Inactive').length
</script>

<template>
  <Head :title="`Gateway: ${gateway.serial_number}`" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="gateways.index().url">
            <Button variant="ghost" size="sm">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Gateways
            </Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
              <Radio class="h-8 w-8" />
              {{ gateway.serial_number }}
              <Badge :class="getStatusColor(gateway.status)" variant="outline">
                {{ gateway.status }}
              </Badge>
            </h1>
            <p class="text-muted-foreground">
              Site: {{ gateway.site.code }}
              <span v-if="gateway.location"> · Location: {{ gateway.location.code }}</span>
            </p>
          </div>
        </div>
        <Link :href="gateways.edit({ gateway: gateway.id }).url">
          <Button>
            <Pencil class="h-4 w-4 mr-2" />
            Edit Gateway
          </Button>
        </Link>
      </div>

      <!-- Stats Cards -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Meters</CardTitle>
            <Zap class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ gateway.meters.length }}</div>
            <p class="text-xs text-muted-foreground">
              {{ activeMeters }} active, {{ inactiveMeters }} inactive
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Connection Status</CardTitle>
            <Radio class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ gateway.status }}</div>
            <p class="text-xs text-muted-foreground">
              {{ gateway.last_log_update ? new Date(gateway.last_log_update).toLocaleString() : 'Never' }}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Software Version</CardTitle>
            <CheckCircle class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ gateway.software_version || '—' }}</div>
            <p class="text-xs text-muted-foreground">Current version</p>
          </CardContent>
        </Card>
      </div>

      <!-- Tabs -->
      <Tabs default-value="details" class="space-y-4">
        <TabsList>
          <TabsTrigger value="details">Details</TabsTrigger>
          <TabsTrigger value="network">Network</TabsTrigger>
          <TabsTrigger value="meters">Meters</TabsTrigger>
          <TabsTrigger value="config">Configuration</TabsTrigger>
        </TabsList>

        <!-- Details Tab -->
        <TabsContent value="details" class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Gateway Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
              <div class="grid grid-cols-2 gap-4">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Serial Number:</span>
                  <span class="font-medium">{{ gateway.serial_number }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Site:</span>
                  <span class="font-medium">{{ gateway.site.code }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Location:</span>
                  <span class="font-medium">{{ gateway.location?.code || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Status:</span>
                  <Badge :class="getStatusColor(gateway.status)" variant="outline">
                    {{ gateway.status }}
                  </Badge>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Software Version:</span>
                  <span class="font-medium">{{ gateway.software_version || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Last Update:</span>
                  <span class="font-medium">
                    {{ gateway.last_log_update ? new Date(gateway.last_log_update).toLocaleString() : 'Never' }}
                  </span>
                </div>
              </div>
              <div v-if="gateway.description" class="pt-4">
                <span class="text-muted-foreground">Description:</span>
                <p class="mt-1">{{ gateway.description }}</p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Network Tab -->
        <TabsContent value="network" class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Network Configuration</CardTitle>
              <CardDescription>Gateway network settings</CardDescription>
            </CardHeader>
            <CardContent class="space-y-2">
              <div class="grid grid-cols-2 gap-4">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">MAC Address:</span>
                  <span class="font-medium font-mono">{{ gateway.mac_address || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">IP Address:</span>
                  <span class="font-medium font-mono">{{ gateway.ip_address || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Netmask:</span>
                  <span class="font-medium font-mono">{{ gateway.ip_netmask || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Gateway IP:</span>
                  <span class="font-medium font-mono">{{ gateway.ip_gateway || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Server IP:</span>
                  <span class="font-medium font-mono">{{ gateway.server_ip || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Connection Type:</span>
                  <span class="font-medium">{{ gateway.connection_type || '—' }}</span>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card v-if="gateway.idf_number || gateway.switch_name || gateway.idf_port">
            <CardHeader>
              <CardTitle>Infrastructure</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
              <div class="grid grid-cols-2 gap-4">
                <div class="flex justify-between">
                  <span class="text-muted-foreground">IDF Number:</span>
                  <span class="font-medium">{{ gateway.idf_number || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">Switch Name:</span>
                  <span class="font-medium">{{ gateway.switch_name || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-muted-foreground">IDF Port:</span>
                  <span class="font-medium">{{ gateway.idf_port || '—' }}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Meters Tab -->
        <TabsContent value="meters" class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Connected Meters</CardTitle>
              <CardDescription>{{ gateway.meters.length }} total meters</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Brand</TableHead>
                    <TableHead>Customer</TableHead>
                    <TableHead>Location</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Last Update</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="gateway.meters.length === 0">
                    <TableCell colspan="7" class="text-center text-muted-foreground">
                      No meters connected to this gateway
                    </TableCell>
                  </TableRow>
                  <TableRow v-for="meter in gateway.meters" :key="meter.id">
                    <TableCell class="font-medium">{{ meter.name }}</TableCell>
                    <TableCell>{{ meter.type }}</TableCell>
                    <TableCell>{{ meter.brand }}</TableCell>
                    <TableCell>{{ meter.customer_name }}</TableCell>
                    <TableCell>{{ meter.location.code }}</TableCell>
                    <TableCell>
                      <Badge :variant="meter.status === 'Active' ? 'default' : 'secondary'">
                        {{ meter.status }}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {{ meter.last_log_update ? new Date(meter.last_log_update).toLocaleString() : 'Never' }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Configuration Tab -->
        <TabsContent value="config" class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Configuration Flags</CardTitle>
              <CardDescription>Gateway behavior settings</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Update CSV</span>
                <div class="flex items-center gap-2">
                  <component :is="gateway.update_csv ? CheckCircle : XCircle" 
                    :class="gateway.update_csv ? 'text-green-500' : 'text-gray-400'" 
                    class="h-5 w-5" />
                  <span class="font-medium">{{ gateway.update_csv ? 'Enabled' : 'Disabled' }}</span>
                </div>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Update Site Code</span>
                <div class="flex items-center gap-2">
                  <component :is="gateway.update_site_code ? CheckCircle : XCircle" 
                    :class="gateway.update_site_code ? 'text-green-500' : 'text-gray-400'" 
                    class="h-5 w-5" />
                  <span class="font-medium">{{ gateway.update_site_code ? 'Enabled' : 'Disabled' }}</span>
                </div>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-muted-foreground">SSH Enabled</span>
                <div class="flex items-center gap-2">
                  <component :is="gateway.ssh_enabled ? CheckCircle : XCircle" 
                    :class="gateway.ssh_enabled ? 'text-green-500' : 'text-gray-400'" 
                    class="h-5 w-5" />
                  <span class="font-medium">{{ gateway.ssh_enabled ? 'Enabled' : 'Disabled' }}</span>
                </div>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Force Load Profile</span>
                <div class="flex items-center gap-2">
                  <component :is="gateway.force_load_profile ? CheckCircle : XCircle" 
                    :class="gateway.force_load_profile ? 'text-green-500' : 'text-gray-400'" 
                    class="h-5 w-5" />
                  <span class="font-medium">{{ gateway.force_load_profile ? 'Enabled' : 'Disabled' }}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
