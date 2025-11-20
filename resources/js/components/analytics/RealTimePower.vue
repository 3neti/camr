<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { Activity, Zap, Power, AlertCircle } from 'lucide-vue-next'
import axios from 'axios'

interface MeterReading {
  meter_id: number
  meter_name: string
  power_kw: number
  voltage: {
    a: number
    b: number
    c: number
    avg: number
  }
  current: {
    a: number
    b: number
    c: number
    avg: number
  }
  power_factor: number
  frequency: number
  timestamp: string
  is_recent: boolean
}

interface RealtimeData {
  timestamp: string
  meters: MeterReading[]
  summary: {
    total_power_kw: number
    meter_count: number
    online_count: number
  }
}

const data = ref<RealtimeData | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const fetchData = async () => {
  try {
    loading.value = true
    error.value = null
    const response = await axios.get('/api/analytics/realtime-power')
    data.value = response.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load real-time data'
  } finally {
    loading.value = false
  }
}

const sortedMeters = computed(() => {
  if (!data.value) return []
  return [...data.value.meters].sort((a, b) => b.power_kw - a.power_kw)
})

onMounted(() => {
  fetchData()
  // Refresh every 30 seconds
  const interval = setInterval(fetchData, 30000)
  return () => clearInterval(interval)
})
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <div>
          <CardTitle class="flex items-center gap-2">
            <Activity class="h-5 w-5" />
            Real-time Power
          </CardTitle>
          <CardDescription>Current power consumption by meter</CardDescription>
        </div>
        <Badge v-if="data" variant="outline" class="bg-green-500/10 text-green-600 border-green-500/20">
          Live
        </Badge>
      </div>
    </CardHeader>
    <CardContent>
      <!-- Loading State -->
      <div v-if="loading" class="space-y-4">
        <div class="flex items-center justify-between">
          <Skeleton class="h-8 w-32" />
          <Skeleton class="h-6 w-20" />
        </div>
        <div class="space-y-3">
          <Skeleton class="h-16 w-full" />
          <Skeleton class="h-16 w-full" />
          <Skeleton class="h-16 w-full" />
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="flex flex-col items-center justify-center py-8 text-center">
        <AlertCircle class="h-12 w-12 text-muted-foreground mb-4" />
        <p class="text-sm text-muted-foreground">{{ error }}</p>
      </div>

      <!-- Data -->
      <div v-else-if="data" class="space-y-6">
        <!-- Summary -->
        <div class="grid grid-cols-3 gap-4">
          <div class="text-center">
            <div class="text-2xl font-bold text-primary">{{ data.summary.total_power_kw }}</div>
            <div class="text-xs text-muted-foreground">Total kW</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold">{{ data.summary.meter_count }}</div>
            <div class="text-xs text-muted-foreground">Meters</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ data.summary.online_count }}</div>
            <div class="text-xs text-muted-foreground">Online</div>
          </div>
        </div>

        <!-- Meter List -->
        <div class="space-y-3">
          <div
            v-for="meter in sortedMeters"
            :key="meter.meter_id"
            class="flex items-center justify-between p-3 rounded-lg border bg-card hover:bg-accent/50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="p-2 rounded-full bg-primary/10">
                <Zap class="h-4 w-4 text-primary" />
              </div>
              <div>
                <div class="font-medium">{{ meter.meter_name }}</div>
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                  <span>{{ meter.voltage.avg.toFixed(1) }}V</span>
                  <span>•</span>
                  <span>{{ meter.current.avg.toFixed(1) }}A</span>
                  <span>•</span>
                  <span>PF {{ meter.power_factor.toFixed(2) }}</span>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-right">
                <div class="text-lg font-bold">{{ meter.power_kw.toFixed(2) }}</div>
                <div class="text-xs text-muted-foreground">kW</div>
              </div>
              <Badge
                :variant="meter.is_recent ? 'default' : 'secondary'"
                :class="meter.is_recent ? 'bg-green-500' : 'bg-gray-500'"
              >
                {{ meter.is_recent ? 'Online' : 'Offline' }}
              </Badge>
            </div>
          </div>
        </div>

        <!-- Last Updated -->
        <div class="text-xs text-muted-foreground text-center">
          Last updated: {{ new Date(data.timestamp).toLocaleString() }}
        </div>
      </div>
    </CardContent>
  </Card>
</template>
