<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import { TrendingUp, Calendar, AlertCircle } from 'lucide-vue-next'
import axios from 'axios'
import { sub, format } from 'date-fns'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

interface Props {
  defaultPeriod?: 'hourly' | 'daily' | 'weekly' | 'monthly'
  days?: number
}

const props = withDefaults(defineProps<Props>(), {
  defaultPeriod: 'daily',
  days: 30
})

interface TrendDataPoint {
  period: string
  total_energy_kwh: number
  avg_power_kw: number
  max_power_kw: number
  meter_count: number
}

interface TrendResponse {
  period_type: string
  start_date: string
  end_date: string
  data: TrendDataPoint[]
}

const data = ref<TrendResponse | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const selectedPeriod = ref(props.defaultPeriod)

const endDate = new Date()
const startDate = sub(endDate, { days: props.days })

const fetchData = async () => {
  try {
    loading.value = true
    error.value = null
    
    const params = {
      period: selectedPeriod.value,
      start_date: format(startDate, 'yyyy-MM-dd'),
      end_date: format(endDate, 'yyyy-MM-dd')
    }
    
    const response = await axios.get('/api/analytics/energy-trend', { params })
    data.value = response.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load energy trend data'
  } finally {
    loading.value = false
  }
}

const chartData = computed(() => {
  if (!data.value) return { labels: [], datasets: [] }
  
  const labels = data.value.data.map(item => {
    if (selectedPeriod.value === 'hourly') {
      return format(new Date(item.period), 'HH:mm')
    } else if (selectedPeriod.value === 'monthly') {
      return format(new Date(item.period + '-01'), 'MMM yyyy')
    } else if (selectedPeriod.value === 'weekly') {
      return item.period
    } else {
      return format(new Date(item.period), 'MMM dd')
    }
  })
  
  return {
    labels,
    datasets: [
      {
        label: 'Energy Consumption (kWh)',
        data: data.value.data.map(item => item.total_energy_kwh),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.4
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: (context: any) => {
          return `Energy: ${context.parsed.y.toFixed(2)} kWh`
        }
      }
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

const stats = computed(() => {
  if (!data.value) return null
  const total = data.value.data.reduce((sum, item) => sum + item.total_energy_kwh, 0)
  const avg = total / data.value.data.length
  const max = Math.max(...data.value.data.map(item => item.total_energy_kwh))
  
  return {
    total: total.toFixed(2),
    average: avg.toFixed(2),
    peak: max.toFixed(2)
  }
})

watch(selectedPeriod, () => {
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
            <TrendingUp class="h-5 w-5" />
            Energy Consumption Trend
          </CardTitle>
          <CardDescription>Historical energy consumption over time</CardDescription>
        </div>
        <Select v-model="selectedPeriod">
          <SelectTrigger class="w-32">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="hourly">Hourly</SelectItem>
            <SelectItem value="daily">Daily</SelectItem>
            <SelectItem value="weekly">Weekly</SelectItem>
            <SelectItem value="monthly">Monthly</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </CardHeader>
    <CardContent>
      <!-- Loading State -->
      <div v-if="loading" class="space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <Skeleton class="h-20 w-full" />
          <Skeleton class="h-20 w-full" />
          <Skeleton class="h-20 w-full" />
        </div>
        <Skeleton class="h-64 w-full" />
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="flex flex-col items-center justify-center py-12 text-center">
        <AlertCircle class="h-12 w-12 text-muted-foreground mb-4" />
        <p class="text-sm text-muted-foreground">{{ error }}</p>
      </div>

      <!-- Data -->
      <div v-else-if="data" class="space-y-6">
        <!-- Stats Summary -->
        <div class="grid grid-cols-3 gap-4">
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-sm text-muted-foreground">Total Consumption</div>
            <div class="text-2xl font-bold mt-1">{{ stats?.total }}</div>
            <div class="text-xs text-muted-foreground">kWh</div>
          </div>
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-sm text-muted-foreground">Average</div>
            <div class="text-2xl font-bold mt-1">{{ stats?.average }}</div>
            <div class="text-xs text-muted-foreground">kWh per period</div>
          </div>
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-sm text-muted-foreground">Peak</div>
            <div class="text-2xl font-bold mt-1">{{ stats?.peak }}</div>
            <div class="text-xs text-muted-foreground">kWh</div>
          </div>
        </div>

        <!-- Chart -->
        <div class="h-64">
          <Line :data="chartData" :options="chartOptions" />
        </div>

        <!-- Period Info -->
        <div class="text-xs text-muted-foreground text-center">
          {{ data.start_date }} to {{ data.end_date }} ({{ selectedPeriod }} aggregation)
        </div>
      </div>
    </CardContent>
  </Card>
</template>
