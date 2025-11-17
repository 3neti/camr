<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import * as locations from '@/actions/App/Http/Controllers/LocationController'
import { ArrowLeft, Pencil, MapPin } from 'lucide-vue-next'

interface Props {
  location: {
    id: number
    code: string
    description: string
    site: { id: number; code: string }
    building: { id: number; code: string; description: string } | null
    gateways: Array<{ id: number; serial_number: string }>
    meters: Array<{ id: number; name: string; type: string; gateway: { serial_number: string } }>
    created_at: string
    updated_at: string
  }
}

const props = defineProps<Props>()
</script>

<template>
  <Head :title="`Location: ${location.code}`" />
  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="locations.index().url">
            <Button variant="ghost" size="sm"><ArrowLeft class="h-4 w-4 mr-2" />Back</Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
              <MapPin class="h-8 w-8" />
              {{ location.code }}
            </h1>
            <p class="text-muted-foreground">{{ location.description }}</p>
          </div>
        </div>
        <Link :href="locations.edit({ location: location.id }).url">
          <Button><Pencil class="h-4 w-4 mr-2" />Edit Location</Button>
        </Link>
      </div>

      <Card>
        <CardHeader><CardTitle>Location Details</CardTitle></CardHeader>
        <CardContent class="space-y-2">
          <div class="grid grid-cols-2 gap-4">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Code:</span>
              <span class="font-medium">{{ location.code }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Site:</span>
              <Badge variant="outline">{{ location.site.code }}</Badge>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Building:</span>
              <span class="font-medium">{{ location.building?.code || 'None' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Created:</span>
              <span class="font-medium">{{ new Date(location.created_at).toLocaleDateString() }}</span>
            </div>
          </div>
          <div class="mt-4">
            <span class="text-sm text-muted-foreground">Description:</span>
            <p class="mt-1">{{ location.description }}</p>
          </div>
        </CardContent>
      </Card>

      <Card v-if="location.gateways.length > 0">
        <CardHeader><CardTitle>Gateways ({{ location.gateways.length }})</CardTitle></CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <Badge v-for="gateway in location.gateways" :key="gateway.id" variant="outline">
              {{ gateway.serial_number }}
            </Badge>
          </div>
        </CardContent>
      </Card>

      <Card v-if="location.meters.length > 0">
        <CardHeader><CardTitle>Meters ({{ location.meters.length }})</CardTitle></CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <Badge v-for="meter in location.meters" :key="meter.id" variant="secondary">
              {{ meter.name }} ({{ meter.type }}) - GW: {{ meter.gateway.serial_number }}
            </Badge>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
