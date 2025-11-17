<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import * as configFiles from '@/actions/App/Http/Controllers/ConfigurationFileController'
import { ArrowLeft, Pencil, FileCode } from 'lucide-vue-next'

interface Props {
  configFile: {
    id: number
    meter_model: string
    config_file_content: string
    meters: Array<{
      id: number
      name: string
      type: string
      gateway: { site: { code: string } }
    }>
    created_at: string
    updated_at: string
  }
}

const props = defineProps<Props>()
</script>

<template>
  <Head :title="`Config: ${configFile.meter_model}`" />
  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="configFiles.index().url">
            <Button variant="ghost" size="sm"><ArrowLeft class="h-4 w-4 mr-2" />Back</Button>
          </Link>
          <div>
            <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
              <FileCode class="h-8 w-8" />
              {{ configFile.meter_model }}
            </h1>
            <p class="text-muted-foreground">{{ configFile.meters.length }} meters using this config</p>
          </div>
        </div>
        <Link :href="configFiles.edit({ configFile: configFile.id }).url">
          <Button><Pencil class="h-4 w-4 mr-2" />Edit Config</Button>
        </Link>
      </div>

      <Card>
        <CardHeader><CardTitle>Configuration Details</CardTitle></CardHeader>
        <CardContent class="space-y-2">
          <div class="grid grid-cols-2 gap-4">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Meter Model:</span>
              <span class="font-medium">{{ configFile.meter_model }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Meters Using:</span>
              <Badge variant="secondary">{{ configFile.meters.length }} meters</Badge>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Created:</span>
              <span class="font-medium">{{ new Date(configFile.created_at).toLocaleDateString() }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Last Updated:</span>
              <span class="font-medium">{{ new Date(configFile.updated_at).toLocaleDateString() }}</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Configuration Content</CardTitle></CardHeader>
        <CardContent>
          <pre class="bg-muted p-4 rounded-md overflow-x-auto text-sm font-mono">{{ configFile.config_file_content }}</pre>
        </CardContent>
      </Card>

      <Card v-if="configFile.meters.length > 0">
        <CardHeader><CardTitle>Meters Using This Configuration ({{ configFile.meters.length }})</CardTitle></CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <Badge v-for="meter in configFile.meters" :key="meter.id" variant="outline">
              {{ meter.name }} ({{ meter.type }}) - {{ meter.gateway.site.code }}
            </Badge>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
