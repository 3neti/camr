<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import * as meters from '@/actions/App/Http/Controllers/MeterController'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { ArrowLeft, Pencil, Zap, CheckCircle, XCircle } from 'lucide-vue-next'

interface Props {
  meter: {
    id: number
    name: string
    type: string
    brand: string
    customer_name: string
    status: string
    status_label: string
    is_addressable: boolean
    has_load_profile: boolean
    last_log_update: string | null
    gateway: { serial_number: string; site: { code: string } }
    location: { code: string; description: string } | null
  }
}

const props = defineProps<Props>()

const getStatusColor = (status: string) => status === 'Online' ? 'bg-green-500' : status === 'Offline' ? 'bg-red-500' : 'bg-gray-500'
</script>

<template>
  <Head :title="`Meter: ${meter.name}`" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="meters.index().url">
            <Button variant="ghost" size="sm"><ArrowLeft class="h-4 w-4 mr-2" />Back</Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
              <Zap class="h-8 w-8" />
              {{ meter.name }}
              <Badge :class="getStatusColor(meter.status_label)" variant="outline">{{ meter.status_label }}</Badge>
            </h1>
            <p class="text-muted-foreground">{{ meter.type }} • {{ meter.brand }}</p>
          </div>
        </div>
        <Link :href="meters.edit({ meter: meter.id }).url">
          <Button><Pencil class="h-4 w-4 mr-2" />Edit Meter</Button>
        </Link>
      </div>

      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Status</CardTitle>
            <Zap class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ meter.status }}</div>
            <p class="text-xs text-muted-foreground">{{ meter.status_label }}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Addressable</CardTitle>
            <component :is="meter.is_addressable ? CheckCircle : XCircle" class="h-4 w-4" :class="meter.is_addressable ? 'text-green-500' : 'text-gray-400'" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ meter.is_addressable ? 'Yes' : 'No' }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Load Profile</CardTitle>
            <component :is="meter.has_load_profile ? CheckCircle : XCircle" class="h-4 w-4" :class="meter.has_load_profile ? 'text-green-500' : 'text-gray-400'" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ meter.has_load_profile ? 'Enabled' : 'Disabled' }}</div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Meter Information</CardTitle></CardHeader>
        <CardContent class="space-y-2">
          <div class="grid grid-cols-2 gap-4">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Name:</span>
              <span class="font-medium">{{ meter.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Type:</span>
              <span class="font-medium">{{ meter.type }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Brand:</span>
              <span class="font-medium">{{ meter.brand }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Customer:</span>
              <span class="font-medium">{{ meter.customer_name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Site:</span>
              <span class="font-medium">{{ meter.gateway.site.code }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Gateway:</span>
              <span class="font-medium">{{ meter.gateway.serial_number }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Location:</span>
              <span class="font-medium">{{ meter.location?.code || '—' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Last Update:</span>
              <span class="font-medium">{{ meter.last_log_update ? new Date(meter.last_log_update).toLocaleString() : 'Never' }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
