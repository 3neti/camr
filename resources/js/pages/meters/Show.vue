<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import * as meters from '@/actions/App/Http/Controllers/MeterController'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { ArrowLeft, Pencil, Zap, CheckCircle, XCircle, TrendingUp, Activity } from 'lucide-vue-next'
import LineChart from '@/components/charts/LineChart.vue'
import axios from 'axios'

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

// Chart data
const powerChartData = ref<{ labels: string[]; datasets: any[] }>({ labels: [], datasets: [] })
const loadProfileChartData = ref<{ labels: string[]; datasets: any[] }>({ labels: [], datasets: [] })
const energySummary = ref<any>(null)
const isLoadingPower = ref(false)
const isLoadingProfile = ref(false)
const isLoadingSummary = ref(false)
const selectedDays = ref(7)

// Fetch power data
async function fetchPowerData() {
  isLoadingPower.value = true
  try {
    const response = await axios.get(`/api/meters/${props.meter.id}/power-data`, {
      params: { days: selectedDays.value, interval: 'hour' }
    })
    
    const data = response.data.data
    powerChartData.value = {
      labels: data.map((d: any) => new Date(d.datetime).toLocaleString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit' 
      })),
      datasets: [
        {
          label: 'Power (W)',
          data: data.map((d: any) => d.power),
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          fill: true,
        },
      ],
    }
  } catch (error) {
    console.error('Error fetching power data:', error)
  } finally {
    isLoadingPower.value = false
  }
}

// Fetch load profile data
async function fetchLoadProfile() {
  if (!props.meter.has_load_profile) return
  
  isLoadingProfile.value = true
  try {
    const response = await axios.get(`/api/meters/${props.meter.id}/load-profile`, {
      params: { days: 1 }
    })
    
    const data = response.data.data
    loadProfileChartData.value = {
      labels: data.map((d: any) => new Date(d.datetime).toLocaleString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit' 
      })),
      datasets: [
        {
          label: 'Delivered Power (kW)',
          data: data.map((d: any) => d.delivered_power),
          borderColor: 'rgb(34, 197, 94)',
          backgroundColor: 'rgba(34, 197, 94, 0.1)',
          fill: true,
        },
        {
          label: 'Received Power (kW)',
          data: data.map((d: any) => d.received_power),
          borderColor: 'rgb(249, 115, 22)',
          backgroundColor: 'rgba(249, 115, 22, 0.1)',
          fill: true,
        },
      ],
    }
  } catch (error) {
    console.error('Error fetching load profile:', error)
  } finally {
    isLoadingProfile.value = false
  }
}

// Fetch energy summary
async function fetchEnergySummary() {
  isLoadingSummary.value = true
  try {
    const response = await axios.get(`/api/meters/${props.meter.id}/energy-summary`, {
      params: { days: 30 }
    })
    energySummary.value = response.data
  } catch (error) {
    console.error('Error fetching energy summary:', error)
  } finally {
    isLoadingSummary.value = false
  }
}

onMounted(() => {
  fetchPowerData()
  fetchLoadProfile()
  fetchEnergySummary()
})
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

      <!-- Energy Summary Stats -->
      <div v-if="energySummary" class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Delivered (30d)</CardTitle>
            <TrendingUp class="h-4 w-4 text-green-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ (energySummary.total_delivered / 1000).toFixed(2) }} kWh</div>
            <p class="text-xs text-muted-foreground">Energy consumed</p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Average Power</CardTitle>
            <Activity class="h-4 w-4 text-blue-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ (energySummary.avg_power / 1000).toFixed(2) }} kW</div>
            <p class="text-xs text-muted-foreground">30-day average</p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Peak Demand</CardTitle>
            <Zap class="h-4 w-4 text-orange-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ (energySummary.peak_power / 1000).toFixed(2) }} kW</div>
            <p class="text-xs text-muted-foreground">Maximum recorded</p>
          </CardContent>
        </Card>
      </div>

      <!-- Charts -->
      <Tabs default-value="power" class="space-y-4">
        <TabsList>
          <TabsTrigger value="power">Power Consumption</TabsTrigger>
          <TabsTrigger value="profile" v-if="meter.has_load_profile">Load Profile</TabsTrigger>
          <TabsTrigger value="details">Details</TabsTrigger>
        </TabsList>

        <TabsContent value="power" class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Power Consumption (Last {{ selectedDays }} Days)</CardTitle>
              <CardDescription>Hourly power readings</CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="isLoadingPower" class="flex items-center justify-center h-[300px]">
                <p class="text-muted-foreground">Loading chart data...</p>
              </div>
              <LineChart
                v-else-if="powerChartData.labels.length > 0"
                :labels="powerChartData.labels"
                :datasets="powerChartData.datasets"
                :height="300"
                y-axis-label="Power (W)"
              />
              <div v-else class="flex items-center justify-center h-[300px]">
                <p class="text-muted-foreground">No data available</p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="profile" class="space-y-4" v-if="meter.has_load_profile">
          <Card>
            <CardHeader>
              <CardTitle>Load Profile (Last 24 Hours)</CardTitle>
              <CardDescription>15-minute interval readings</CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="isLoadingProfile" class="flex items-center justify-center h-[300px]">
                <p class="text-muted-foreground">Loading chart data...</p>
              </div>
              <LineChart
                v-else-if="loadProfileChartData.labels.length > 0"
                :labels="loadProfileChartData.labels"
                :datasets="loadProfileChartData.datasets"
                :height="300"
                y-axis-label="Power (kW)"
              />
              <div v-else class="flex items-center justify-center h-[300px]">
                <p class="text-muted-foreground">No load profile data available</p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="details" class="space-y-4">
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
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
