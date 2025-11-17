<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import * as buildings from '@/actions/App/Http/Controllers/BuildingController'
import { ArrowLeft, Pencil, Building2 } from 'lucide-vue-next'

interface Props {
  building: {
    id: number
    code: string
    description: string | null
    site: { id: number; code: string }
    locations: Array<{ id: number; code: string; description: string }>
    meters: Array<{ id: number; name: string; type: string }>
    created_at: string
  }
}

const props = defineProps<Props>()
</script>

<template>
  <Head :title="`Building: ${building.code}`" />
  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="buildings.index().url">
            <Button variant="ghost" size="sm"><ArrowLeft class="h-4 w-4 mr-2" />Back</Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
              <Building2 class="h-8 w-8" />
              {{ building.code }}
            </h1>
            <p class="text-muted-foreground">{{ building.site.code }}</p>
          </div>
        </div>
        <Link :href="buildings.edit({ building: building.id }).url">
          <Button><Pencil class="h-4 w-4 mr-2" />Edit Building</Button>
        </Link>
      </div>

      <Card>
        <CardHeader><CardTitle>Building Information</CardTitle></CardHeader>
        <CardContent class="space-y-2">
          <div class="grid grid-cols-2 gap-4">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Code:</span>
              <span class="font-medium">{{ building.code }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Site:</span>
              <Badge variant="outline">{{ building.site.code }}</Badge>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Locations:</span>
              <span class="font-medium">{{ building.locations.length }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Meters:</span>
              <span class="font-medium">{{ building.meters.length }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Created:</span>
              <span class="font-medium">{{ new Date(building.created_at).toLocaleDateString() }}</span>
            </div>
          </div>
          <div v-if="building.description" class="pt-4">
            <span class="text-muted-foreground">Description:</span>
            <p class="mt-1">{{ building.description }}</p>
          </div>
        </CardContent>
      </Card>

      <Card v-if="building.locations.length > 0">
        <CardHeader><CardTitle>Locations ({{ building.locations.length }})</CardTitle></CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <Badge v-for="location in building.locations" :key="location.id" variant="outline">
              {{ location.code }} - {{ location.description }}
            </Badge>
          </div>
        </CardContent>
      </Card>

      <Card v-if="building.meters.length > 0">
        <CardHeader><CardTitle>Meters ({{ building.meters.length }})</CardTitle></CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <Badge v-for="meter in building.meters" :key="meter.id">
              {{ meter.name }} ({{ meter.type }})
            </Badge>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
