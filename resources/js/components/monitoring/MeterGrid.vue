<script setup lang="ts">
import { computed, watch, ref } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Zap } from 'lucide-vue-next'

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

interface Props {
  meters: MeterReading[]
  loading: boolean
}

const props = defineProps<Props>()

const flashSet = ref<Set<number>>(new Set())

watch(
  () => props.meters,
  (newMeters, oldMeters) => {
    const oldMap = new Map<number, MeterReading>()
    oldMeters?.forEach(m => oldMap.set(m.meter_id, m))
    newMeters?.forEach(m => {
      const prev = oldMap.get(m.meter_id)
      if (!prev || prev.timestamp !== m.timestamp || prev.power_kw !== m.power_kw) {
        flashSet.value.add(m.meter_id)
        setTimeout(() => {
          flashSet.value.delete(m.meter_id)
        }, 800)
      }
    })
  },
  { deep: true }
)
</script>

<template>
  <div>
    <div v-if="loading" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="i in 6" :key="i" class="h-28 rounded-lg border bg-muted/30 animate-pulse" />
    </div>

    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <Card
        v-for="m in meters"
        :key="m.meter_id"
        class="transition-colors border-2"
        :class="[
          m.is_recent ? 'border-green-200' : 'border-red-200',
          flashSet.has(m.meter_id) ? 'ring-2 ring-primary/50' : ''
        ]"
      >
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="p-2 rounded-full bg-primary/10">
                <Zap class="h-4 w-4 text-primary" />
              </div>
              <div class="font-medium">{{ m.meter_name }}</div>
            </div>
            <Badge :class="m.is_recent ? 'bg-green-500' : 'bg-gray-500'">
              {{ m.is_recent ? 'Online' : 'Offline' }}
            </Badge>
          </div>
          
          <div class="mt-3 grid grid-cols-3 items-end gap-3">
            <div>
              <div class="text-xs text-muted-foreground">Power</div>
              <div class="text-xl font-bold">{{ m.power_kw.toFixed(2) }} <span class="text-xs font-normal">kW</span></div>
            </div>
            <div>
              <div class="text-xs text-muted-foreground">Voltage</div>
              <div class="font-medium">{{ m.voltage.avg.toFixed(0) }}V</div>
            </div>
            <div>
              <div class="text-xs text-muted-foreground">Current</div>
              <div class="font-medium">{{ m.current.avg.toFixed(1) }}A</div>
            </div>
          </div>
          
          <div class="mt-2 flex items-center justify-between text-xs text-muted-foreground">
            <div>PF {{ m.power_factor.toFixed(2) }} • {{ m.frequency.toFixed(2) }} Hz</div>
            <div>{{ new Date(m.timestamp).toLocaleTimeString() }}</div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
