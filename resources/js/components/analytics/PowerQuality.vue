<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
import { Zap, AlertCircle } from 'lucide-vue-next'
import axios from 'axios'
import { sub, format } from 'date-fns'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

interface PowerQualityData {
  period: { start: string; end: string }
  total_readings: number
  voltage: {
    avg: number
    min: number
    max: number
    by_phase: { a: number; b: number; c: number }
  }
  current: {
    avg: number
    min: number
    max: number
    by_phase: { a: number; b: number; c: number }
  }
  power_factor: {
    avg: number
    min: number
    max: number
    low_pf_percentage: number
  }
  frequency: {
    avg: number
    min: number
    max: number
  }
}

const data = ref<PowerQualityData | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const fetchData = async () => {
  try {
    loading.value = true
    error.value = null
    
    const endDate = new Date()
    const startDate = sub(endDate, { days: 7 })
    
    const params = {
      start_date: format(startDate, 'yyyy-MM-dd'),
      end_date: format(endDate, 'yyyy-MM-dd')
    }
    
    const response = await axios.get('/api/analytics/power-quality', { params })
    data.value = response.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load power quality data'
  } finally {
    loading.value = false
  }
}

const voltageChartData = computed(() => {
  if (!data.value) return { labels: [], datasets: [] }
  
  return {
    labels: ['Phase A', 'Phase B', 'Phase C'],
    datasets: [{
      label: 'Voltage (V)',
      data: [
        data.value.voltage.by_phase.a,
        data.value.voltage.by_phase.b,
        data.value.voltage.by_phase.c
      ],
      backgroundColor: ['rgba(239, 68, 68, 0.5)', 'rgba(234, 179, 8, 0.5)', 'rgba(34, 197, 94, 0.5)'],
      borderColor: ['rgb(239, 68, 68)', 'rgb(234, 179, 8)', 'rgb(34, 197, 94)'],
      borderWidth: 2
    }]
  }
})

const currentChartData = computed(() => {
  if (!data.value) return { labels: [], datasets: [] }
  
  return {
    labels: ['Phase A', 'Phase B', 'Phase C'],
    datasets: [{
      label: 'Current (A)',
      data: [
        data.value.current.by_phase.a,
        data.value.current.by_phase.b,
        data.value.current.by_phase.c
      ],
      backgroundColor: ['rgba(239, 68, 68, 0.5)', 'rgba(234, 179, 8, 0.5)', 'rgba(34, 197, 94, 0.5)'],
      borderColor: ['rgb(239, 68, 68)', 'rgb(234, 179, 8)', 'rgb(34, 197, 94)'],
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
      beginAtZero: true
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
        <Zap class="h-5 w-5" />
        Power Quality Metrics
      </CardTitle>
      <CardDescription>Last 7 days electrical parameters analysis</CardDescription>
    </CardHeader>
    <CardContent>
      <!-- Loading State -->
      <div v-if="loading" class="space-y-4">
        <div class="grid grid-cols-4 gap-4">
          <Skeleton class="h-24 w-full" />
          <Skeleton class="h-24 w-full" />
          <Skeleton class="h-24 w-full" />
          <Skeleton class="h-24 w-full" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <Skeleton class="h-64 w-full" />
          <Skeleton class="h-64 w-full" />
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="flex flex-col items-center justify-center py-12 text-center">
        <AlertCircle class="h-12 w-12 text-muted-foreground mb-4" />
        <p class="text-sm text-muted-foreground">{{ error }}</p>
      </div>

      <!-- Data -->
      <div v-else-if="data" class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-xs text-muted-foreground">Avg Voltage</div>
            <div class="text-2xl font-bold mt-1">{{ data.voltage.avg.toFixed(1) }}</div>
            <div class="text-xs text-muted-foreground">
              {{ data.voltage.min.toFixed(1) }} - {{ data.voltage.max.toFixed(1) }} V
            </div>
          </div>
          
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-xs text-muted-foreground">Avg Current</div>
            <div class="text-2xl font-bold mt-1">{{ data.current.avg.toFixed(1) }}</div>
            <div class="text-xs text-muted-foreground">
              {{ data.current.min.toFixed(1) }} - {{ data.current.max.toFixed(1) }} A
            </div>
          </div>
          
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-xs text-muted-foreground">Power Factor</div>
            <div class="text-2xl font-bold mt-1" :class="data.power_factor.avg >= 0.95 ? 'text-green-600' : 'text-yellow-600'">
              {{ data.power_factor.avg.toFixed(3) }}
            </div>
            <div class="text-xs text-muted-foreground">
              {{ data.power_factor.low_pf_percentage.toFixed(1) }}% low PF
            </div>
          </div>
          
          <div class="p-4 rounded-lg border bg-card">
            <div class="text-xs text-muted-foreground">Frequency</div>
            <div class="text-2xl font-bold mt-1">{{ data.frequency.avg.toFixed(2) }}</div>
            <div class="text-xs text-muted-foreground">Hz</div>
          </div>
        </div>

        <!-- Phase Charts -->
        <div class="grid md:grid-cols-2 gap-4">
          <div class="p-4 rounded-lg border bg-card">
            <h3 class="text-sm font-medium mb-4">Voltage by Phase</h3>
            <div class="h-48">
              <Bar :data="voltageChartData" :options="chartOptions" />
            </div>
          </div>
          
          <div class="p-4 rounded-lg border bg-card">
            <h3 class="text-sm font-medium mb-4">Current by Phase</h3>
            <div class="h-48">
              <Bar :data="currentChartData" :options="chartOptions" />
            </div>
          </div>
        </div>

        <!-- Readings Info -->
        <div class="text-xs text-muted-foreground text-center">
          Based on {{ data.total_readings.toLocaleString() }} readings from {{ data.period.start }} to {{ data.period.end }}
        </div>
      </div>
    </CardContent>
  </Card>
</template>
