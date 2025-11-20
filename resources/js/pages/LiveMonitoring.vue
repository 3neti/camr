<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import MeterGrid from '@/components/monitoring/MeterGrid.vue'
import ActivityFeed from '@/components/monitoring/ActivityFeed.vue'
import { Radio, Play, Pause, Search, Filter } from 'lucide-vue-next'
import axios from 'axios'

interface MeterReading {
  meter_id: number
  meter_name: string
  power_kw: number
  voltage: { a: number; b: number; c: number; avg: number }
  current: { a: number; b: number; c: number; avg: number }
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
const activityLog = ref<MeterReading[]>([])
const loading = ref(true)
const isPaused = ref(false)
const refreshInterval = ref(5) // seconds
const searchQuery = ref('')
const statusFilter = ref<'all' | 'online' | 'offline'>('all')
let intervalId: number | null = null

const breadcrumbs = [
  { label: 'Dashboard', url: '/dashboard' },
  { label: 'Live Monitoring', url: '/live-monitoring' },
]

const fetchData = async () => {
  if (isPaused.value) return
  
  try {
    const response = await axios.get('/api/analytics/realtime-power')
    const newData = response.data
    
    // Add new readings to activity log
    if (data.value) {
      newData.meters.forEach((meter: MeterReading) => {
        const oldMeter = data.value!.meters.find(m => m.meter_id === meter.meter_id)
        if (!oldMeter || oldMeter.timestamp !== meter.timestamp) {
          activityLog.value.unshift({
            ...meter,
            timestamp: new Date().toISOString()
          })
        }
      })
      
      // Keep only last 100 readings
      activityLog.value = activityLog.value.slice(0, 100)
    }
    
    data.value = newData
    loading.value = false
  } catch (err) {
    console.error('Failed to fetch live data:', err)
  }
}

const filteredMeters = computed(() => {
  if (!data.value) return []
  
  let filtered = data.value.meters
  
  // Status filter
  if (statusFilter.value === 'online') {
    filtered = filtered.filter(m => m.is_recent)
  } else if (statusFilter.value === 'offline') {
    filtered = filtered.filter(m => !m.is_recent)
  }
  
  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(m => m.meter_name.toLowerCase().includes(query))
  }
  
  return filtered
})

const togglePause = () => {
  isPaused.value = !isPaused.value
  if (!isPaused.value) {
    fetchData()
  }
}

const changeRefreshRate = (rate: string) => {
  refreshInterval.value = parseInt(rate)
  if (intervalId) {
    clearInterval(intervalId)
  }
  startPolling()
}

const startPolling = () => {
  intervalId = window.setInterval(fetchData, refreshInterval.value * 1000)
}

onMounted(() => {
  fetchData()
  startPolling()
})

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId)
  }
})
</script>

<template>
  <Head title="Live Monitoring" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto py-6 space-y-6">
      <!-- Header with Controls -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
            <Radio class="h-8 w-8" />
            Live Monitoring
          </h1>
          <p class="text-muted-foreground">Real-time meter data stream</p>
        </div>
        
        <div class="flex items-center gap-4">
          <Badge v-if="data" variant="outline" :class="isPaused ? 'bg-yellow-500/10 text-yellow-600' : 'bg-green-500/10 text-green-600'">
            {{ isPaused ? '⏸️ Paused' : '🟢 Live' }}
          </Badge>
          
          <Select :model-value="refreshInterval.toString()" @update:model-value="changeRefreshRate">
            <SelectTrigger class="w-32">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="3">3 seconds</SelectItem>
              <SelectItem value="5">5 seconds</SelectItem>
              <SelectItem value="10">10 seconds</SelectItem>
              <SelectItem value="30">30 seconds</SelectItem>
            </SelectContent>
          </Select>
          
          <Button @click="togglePause" variant="outline" size="sm">
            <Pause v-if="!isPaused" class="h-4 w-4 mr-2" />
            <Play v-else class="h-4 w-4 mr-2" />
            {{ isPaused ? 'Resume' : 'Pause' }}
          </Button>
        </div>
      </div>

      <!-- Summary Bar -->
      <Card v-if="data">
        <CardContent class="py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-8">
              <div>
                <div class="text-sm text-muted-foreground">Total Power</div>
                <div class="text-2xl font-bold">{{ data.summary.total_power_kw.toFixed(2) }} kW</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Meters</div>
                <div class="text-2xl font-bold">{{ data.summary.meter_count }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Online</div>
                <div class="text-2xl font-bold text-green-600">{{ data.summary.online_count }}</div>
              </div>
              <div>
                <div class="text-sm text-muted-foreground">Offline</div>
                <div class="text-2xl font-bold text-red-600">{{ data.summary.meter_count - data.summary.online_count }}</div>
              </div>
            </div>
            
            <div class="text-xs text-muted-foreground">
              Last updated: {{ new Date(data.timestamp).toLocaleTimeString() }}
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Filters -->
      <div class="flex items-center gap-4">
        <div class="relative flex-1 max-w-sm">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            v-model="searchQuery"
            placeholder="Search meters..."
            class="pl-10"
          />
        </div>
        
        <Select v-model="statusFilter">
          <SelectTrigger class="w-40">
            <Filter class="h-4 w-4 mr-2" />
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Meters</SelectItem>
            <SelectItem value="online">Online Only</SelectItem>
            <SelectItem value="offline">Offline Only</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Main Content: Grid + Activity Feed -->
      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Meter Grid (2/3 width) -->
        <div class="lg:col-span-2">
          <MeterGrid :meters="filteredMeters" :loading="loading" />
        </div>

        <!-- Activity Feed (1/3 width) -->
        <div>
          <ActivityFeed :readings="activityLog" :is-paused="isPaused" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
