<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
  Legend
} from 'chart.js'
import { BarChart3, AlertCircle } from 'lucide-vue-next'
import axios from 'axios'
import { sub, format } from 'date-fns'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

interface DemandData {
  period: { start: string; end: string }
  summary: {
    peak_demand_kw: number
    avg_demand_kw: number
    peak_kvar_demand: number
    avg_kvar_demand: number
  }
  daily_peaks: Array<{
    date: string
    peak_demand_kw: number
    avg_demand_kw: number
  }>
}

const data = ref<DemandData | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const fetchData = async () => {
  try {
    loading.value = true
    error.value = null
    
    const endDate = new Date()
    const startDate = sub(endDate, { days: 30 })
    
    const params = {
      start_date: format(startDate, 'yyyy-MM-dd'),
      end_date: format(endDate, 'yyyy-MM-dd')
    }
    
    const response = await axios.get('/api/analytics/demand-analysis', { params })
    data.value = response.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load demand data'
  } finally {
    loading.value = false
  }
}

const chartData = computed(() => {
  if (!data.value) return { labels: [], datasets: [] }
  
  return {
    labels: data.value.daily_peaks.map(item => format(new Date(item.date), 'MMM dd')),
    datasets: [
      {
        label: 'Peak Demand (kW)',
        data: data.value.daily_peaks.map(item => item.peak_demand_kw),
        borderColor: 'rgb(239, 68, 68)',
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        tension: 0.4
      },
      {
        label: 'Average Demand (kW)',
        data: data.value.daily_peaks.map(item => item.avg_demand_kw),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
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
      position: 'top' as const
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (value: any) => `${value} kW`
      }
    }
  }
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="flex items-center gap-2">
        <BarChart3 class="h-5 w-5" />
        Demand Analysis
      </CardTitle>
      <CardDescription>Peak and average demand tracking (last 30 days)</CardDescription>
    </CardHeader>
    <CardContent>
      <div v-if="loading" class="space-y-4">
        <div class="grid grid-cols-4 gap-4">
          <Skeleton class="h-24 w-full" />
          <Skeleton class="h-24 w-full" />
          <Skeleton class="h-24 w-full" />
          <Skeleton class="h-24 w-full" />
        </div>
        <Skeleton class="h-64 w-full" />
      </div>

      <div v-else-if="error" class="flex flex-col items-center justify-center py-12 text-center">
        <AlertCircle class="h-12 w-12 text-muted-foreground mb-4" />
        <p class="text-sm text-muted-foreground">{{ error }}</p>
      </div>

      <div v-else-if="data" class="space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-xs text-muted-foreground">Peak Demand</div>
            <div class="text-2xl font-bold text-red-600 mt-1">{{ data.summary.peak_demand_kw.toFixed(2) }}</div>
            <div class="text-xs text-muted-foreground">kW</div>
          </div>
          
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-xs text-muted-foreground">Avg Demand</div>
            <div class="text-2xl font-bold mt-1">{{ data.summary.avg_demand_kw.toFixed(2) }}</div>
            <div class="text-xs text-muted-foreground">kW</div>
          </div>
          
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-xs text-muted-foreground">Peak kVAR</div>
            <div class="text-2xl font-bold mt-1">{{ data.summary.peak_kvar_demand.toFixed(2) }}</div>
            <div class="text-xs text-muted-foreground">kVAR</div>
          </div>
          
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-xs text-muted-foreground">Avg kVAR</div>
            <div class="text-2xl font-bold mt-1">{{ data.summary.avg_kvar_demand.toFixed(2) }}</div>
            <div class="text-xs text-muted-foreground">kVAR</div>
          </div>
        </div>

        <div class="h-64">
          <Line :data="chartData" :options="chartOptions" />
        </div>

        <div class="text-xs text-muted-foreground text-center">
          {{ data.period.start }} to {{ data.period.end }}
        </div>
      </div>
    </CardContent>
  </Card>
</template>
