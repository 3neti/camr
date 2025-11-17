<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { BarChart3, Download, Zap, Activity, TrendingUp, Building2 } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import axios from 'axios'
import DateRangePicker from '@/components/DateRangePicker.vue'

interface Props {
  sites: Array<{
    id: number
    code: string
    name: string
    meters_count: number
  }>
  meters: Array<{
    id: number
    name: string
    type: string
    site: string
    has_load_profile: boolean
    latest_power: number | null
    latest_energy: number | null
    last_reading: string | null
  }>
  stats: {
    total_meters: number
    active_meters: number
    meters_with_data: number
  }
}

const props = defineProps<Props>()

const selectedSite = ref<string>('all')
const selectedMeter = ref<string>('')
const exportFormat = ref<string>('csv')
const interval = ref<'hour' | 'day'>('hour')
const isExporting = ref(false)
const selectedRange = ref<{ start: Date; end: Date } | null>(null)

async function exportData() {
  if (!selectedMeter.value) {
    alert('Please select a meter to export')
    return
  }
  
  // Determine date range
  const now = new Date()
  const defaultStart = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
  const start = selectedRange.value?.start || defaultStart
  const end = selectedRange.value?.end || now

  const formatDate = (d: Date) => {
    const tzOffsetMs = d.getTimezoneOffset() * 60000
    return new Date(d.getTime() - tzOffsetMs).toISOString().slice(0, 10)
  }

  isExporting.value = true
  try {
    const response = await axios.get(`/api/meters/${selectedMeter.value}/power-data`, {
      params: { 
        start_date: formatDate(start),
        end_date: formatDate(end),
        interval: interval.value,
      },
    })

    // Prepare file based on format
    if (exportFormat.value === 'json') {
      const blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `meter-${selectedMeter.value}-data.json`
      a.click()
      window.URL.revokeObjectURL(url)
    } else if (exportFormat.value === 'xlsx') {
      alert('Excel (XLSX) export not yet supported. Please use CSV or JSON.')
      return
    } else {
      const csvContent = convertToCSV(response.data.data)
      const blob = new Blob([csvContent], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `meter-${selectedMeter.value}-data.csv`
      a.click()
      window.URL.revokeObjectURL(url)
    }
  } catch (error) {
    console.error('Export error:', error)
    alert('Error exporting data')
  } finally {
    isExporting.value = false
  }
}

function convertToCSV(data: any[]): string {
  if (data.length === 0) return ''
  
  const headers = Object.keys(data[0])
  const rows = data.map(row => 
    headers.map(header => JSON.stringify(row[header] ?? '')).join(',')
  )
  
  return [headers.join(','), ...rows].join('\n')
}

const filteredMeters = ref(
  selectedSite.value === 'all' 
    ? props.meters 
    : props.meters.filter(m => m.site === selectedSite.value)
)

function updateFilteredMeters(siteCode: string) {
  selectedSite.value = siteCode
  filteredMeters.value = siteCode === 'all' 
    ? props.meters 
    : props.meters.filter(m => m.site === siteCode)
}
</script>

<template>
  <Head title="Reports & Analytics" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
            <BarChart3 class="h-8 w-8" />
            Reports & Analytics
          </h1>
          <p class="text-muted-foreground">Energy monitoring and data export</p>
        </div>
      </div>

      <!-- Stats Overview -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Meters</CardTitle>
            <Zap class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total_meters }}</div>
            <p class="text-xs text-muted-foreground">
              {{ stats.active_meters }} active
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Meters with Data</CardTitle>
            <Activity class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.meters_with_data }}</div>
            <p class="text-xs text-muted-foreground">
              {{ ((stats.meters_with_data / stats.total_meters) * 100).toFixed(1) }}% coverage
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Sites</CardTitle>
            <Building2 class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ sites.length }}</div>
            <p class="text-xs text-muted-foreground">
              {{ sites.reduce((sum, s) => sum + s.meters_count, 0) }} total meters
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Export Section -->
      <Card>
        <CardHeader>
          <CardTitle>Export Meter Data</CardTitle>
          <CardDescription>Download historical energy consumption data</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <DateRangePicker v-model="selectedRange" />
          <div class="grid gap-4 md:grid-cols-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Site</label>
              <Select :model-value="selectedSite" @update:model-value="updateFilteredMeters">
                <SelectTrigger>
                  <SelectValue placeholder="Select site" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Sites</SelectItem>
                  <SelectItem v-for="site in sites" :key="site.id" :value="site.code">
                    {{ site.code }} - {{ site.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Meter</label>
              <Select v-model="selectedMeter">
                <SelectTrigger>
                  <SelectValue placeholder="Select meter" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="meter in filteredMeters" :key="meter.id" :value="meter.id.toString()">
                    {{ meter.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Format</label>
              <Select v-model="exportFormat">
                <SelectTrigger>
                  <SelectValue placeholder="Select format" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="csv">CSV</SelectItem>
                  <SelectItem value="json">JSON</SelectItem>
                  <SelectItem value="xlsx">Excel (XLSX)</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Interval</label>
              <Select v-model="interval">
                <SelectTrigger>
                  <SelectValue placeholder="Select interval" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="hour">Hourly</SelectItem>
                  <SelectItem value="day">Daily</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <Button @click="exportData" :disabled="!selectedMeter || isExporting" class="w-full">
            <Download class="h-4 w-4 mr-2" />
            {{ isExporting ? 'Exporting...' : 'Export Data (Last 30 Days)' }}
          </Button>
        </CardContent>
      </Card>

      <!-- Recent Meter Readings -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Meter Readings</CardTitle>
          <CardDescription>Latest power consumption data</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Meter</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Site</TableHead>
                <TableHead>Current Power</TableHead>
                <TableHead>Total Energy</TableHead>
                <TableHead>Load Profile</TableHead>
                <TableHead>Last Reading</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="meter in meters" :key="meter.id">
                <TableCell class="font-medium">{{ meter.name }}</TableCell>
                <TableCell>{{ meter.type }}</TableCell>
                <TableCell>{{ meter.site }}</TableCell>
                <TableCell>
                  {{ meter.latest_power ? (meter.latest_power / 1000).toFixed(2) + ' kW' : '—' }}
                </TableCell>
                <TableCell>
                  {{ meter.latest_energy ? (meter.latest_energy / 1000).toFixed(2) + ' kWh' : '—' }}
                </TableCell>
                <TableCell>
                  <Badge v-if="meter.has_load_profile" variant="default">Enabled</Badge>
                  <Badge v-else variant="secondary">Disabled</Badge>
                </TableCell>
                <TableCell>
                  {{ meter.last_reading ? new Date(meter.last_reading).toLocaleString() : 'Never' }}
                </TableCell>
                <TableCell>
                  <Link :href="`/meters/${meter.id}`">
                    <Button size="sm" variant="ghost">
                      <TrendingUp class="h-4 w-4" />
                    </Button>
                  </Link>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
