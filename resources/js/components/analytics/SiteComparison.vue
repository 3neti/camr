<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import { PieChart, AlertCircle } from 'lucide-vue-next'
import axios from 'axios'
import { sub, format } from 'date-fns'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

interface SiteData {
  group_by: string
  period: { start: string; end: string }
  data: Array<{
    group_name: string
    group_id: number | null
    site_code: string | null
    total_energy_kwh: number
    avg_power_kw: number
    max_power_kw: number
    meter_count: number
  }>
  summary: {
    total_energy_kwh: number
    total_groups: number
    total_meters: number
  }
}

const data = ref<SiteData | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const groupBy = ref<'site' | 'building' | 'location'>('site')

const fetchData = async () => {
  try {
    loading.value = true
    error.value = null
    
    const endDate = new Date()
    const startDate = sub(endDate, { days: 7 })
    
    const params = {
      group_by: groupBy.value,
      start_date: format(startDate, 'yyyy-MM-dd'),
      end_date: format(endDate, 'yyyy-MM-dd')
    }
    
    const response = await axios.get('/api/analytics/site-aggregation', { params })
    data.value = response.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load site data'
  } finally {
    loading.value = false
  }
}

const chartData = computed(() => {
  if (!data.value) return { labels: [], datasets: [] }
  
  return {
    labels: data.value.data.map(item => item.group_name),
    datasets: [{
      label: 'Energy Consumption (kWh)',
      data: data.value.data.map(item => item.total_energy_kwh),
      backgroundColor: 'rgba(59, 130, 246, 0.5)',
      borderColor: 'rgb(59, 130, 246)',
      borderWidth: 2
    }]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (value: any) => `${value} kWh`
      }
    }
  }
}

watch(groupBy, () => {
  fetchData()
})

onMounted(() => {
  fetchData()
})
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <div>
          <CardTitle class="flex items-center gap-2">
            <PieChart class="h-5 w-5" />
            Site Comparison
          </CardTitle>
          <CardDescription>Energy consumption by {{ groupBy }} (last 7 days)</CardDescription>
        </div>
        <Select v-model="groupBy">
          <SelectTrigger class="w-32">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="site">Site</SelectItem>
            <SelectItem value="building">Building</SelectItem>
            <SelectItem value="location">Location</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </CardHeader>
    <CardContent>
      <div v-if="loading" class="space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <Skeleton class="h-20 w-full" />
          <Skeleton class="h-20 w-full" />
          <Skeleton class="h-20 w-full" />
        </div>
        <Skeleton class="h-64 w-full" />
      </div>

      <div v-else-if="error" class="flex flex-col items-center justify-center py-12 text-center">
        <AlertCircle class="h-12 w-12 text-muted-foreground mb-4" />
        <p class="text-sm text-muted-foreground">{{ error }}</p>
      </div>

      <div v-else-if="data" class="space-y-6">
        <div class="grid grid-cols-3 gap-4">
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-xs text-muted-foreground">Total Consumption</div>
            <div class="text-2xl font-bold mt-1">{{ data.summary.total_energy_kwh.toFixed(2) }}</div>
            <div class="text-xs text-muted-foreground">kWh</div>
          </div>
          
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-xs text-muted-foreground">Total {{ groupBy }}s</div>
            <div class="text-2xl font-bold mt-1">{{ data.summary.total_groups }}</div>
            <div class="text-xs text-muted-foreground">groups</div>
          </div>
          
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-xs text-muted-foreground">Total Meters</div>
            <div class="text-2xl font-bold mt-1">{{ data.summary.total_meters }}</div>
            <div class="text-xs text-muted-foreground">meters</div>
          </div>
        </div>

        <div class="h-64">
          <Bar :data="chartData" :options="chartOptions" />
        </div>

        <div class="text-xs text-muted-foreground text-center">
          {{ data.period.start }} to {{ data.period.end }}
        </div>
      </div>
    </CardContent>
  </Card>
</template>
