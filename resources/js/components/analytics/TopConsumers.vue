<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { Award, Trophy, Medal, AlertCircle } from 'lucide-vue-next'
import axios from 'axios'
import { sub, format } from 'date-fns'

interface Props {
  limit?: number
}

const props = withDefaults(defineProps<Props>(), {
  limit: 10
})

interface ConsumerData {
  period: { start: string; end: string }
  data: Array<{
    meter_name: string
    energy_consumed_kwh: number
    avg_power_kw: number
    max_power_kw: number
    avg_power_factor: number
  }>
}

const data = ref<ConsumerData | null>(null)
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
      end_date: format(endDate, 'yyyy-MM-dd'),
      limit: props.limit
    }
    
    const response = await axios.get('/api/analytics/top-consumers', { params })
    data.value = response.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load top consumers'
  } finally {
    loading.value = false
  }
}

const getRankIcon = (index: number) => {
  if (index === 0) return Trophy
  if (index === 1) return Medal
  if (index === 2) return Medal
  return Award
}

const getRankColor = (index: number) => {
  if (index === 0) return 'text-yellow-500'
  if (index === 1) return 'text-gray-400'
  if (index === 2) return 'text-amber-600'
  return 'text-muted-foreground'
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="flex items-center gap-2">
        <Award class="h-5 w-5" />
        Top Energy Consumers
      </CardTitle>
      <CardDescription>Highest consuming meters (last 7 days)</CardDescription>
    </CardHeader>
    <CardContent>
      <div v-if="loading" class="space-y-3">
        <Skeleton v-for="i in limit" :key="i" class="h-16 w-full" />
      </div>

      <div v-else-if="error" class="flex flex-col items-center justify-center py-8 text-center">
        <AlertCircle class="h-12 w-12 text-muted-foreground mb-4" />
        <p class="text-sm text-muted-foreground">{{ error }}</p>
      </div>

      <div v-else-if="data" class="space-y-3">
        <div
          v-for="(consumer, index) in data.data"
          :key="consumer.meter_name"
          class="flex items-center gap-4 p-4 rounded-lg border bg-card hover:bg-accent/50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <component
              :is="getRankIcon(index)"
              class="h-5 w-5"
              :class="getRankColor(index)"
            />
            <div class="text-2xl font-bold text-muted-foreground min-w-[2ch]">
              {{ index + 1 }}
            </div>
          </div>
          
          <div class="flex-1">
            <div class="font-medium">{{ consumer.meter_name }}</div>
            <div class="flex items-center gap-3 text-xs text-muted-foreground mt-1">
              <span>Avg: {{ consumer.avg_power_kw.toFixed(2) }} kW</span>
              <span>•</span>
              <span>Peak: {{ consumer.max_power_kw.toFixed(2) }} kW</span>
              <span>•</span>
              <span>PF: {{ consumer.avg_power_factor.toFixed(2) }}</span>
            </div>
          </div>
          
          <div class="text-right">
            <div class="text-lg font-bold">{{ consumer.energy_consumed_kwh.toFixed(2) }}</div>
            <div class="text-xs text-muted-foreground">kWh</div>
          </div>
        </div>

        <div v-if="data.data.length === 0" class="text-center py-8 text-muted-foreground">
          No consumption data available
        </div>

        <div v-else class="text-xs text-muted-foreground text-center pt-2">
          {{ data.period.start }} to {{ data.period.end }}
        </div>
      </div>
    </CardContent>
  </Card>
</template>
