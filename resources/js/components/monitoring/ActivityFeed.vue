<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Activity } from 'lucide-vue-next'

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
  readings: MeterReading[]
  isPaused: boolean
}

const props = defineProps<Props>()

const feedContainer = ref<HTMLElement>()
const shouldAutoScroll = ref(true)

const formatTimeAgo = (timestamp: string) => {
  const now = new Date()
  const past = new Date(timestamp)
  const diffMs = now.getTime() - past.getTime()
  const diffSec = Math.floor(diffMs / 1000)
  
  if (diffSec < 1) return 'just now'
  if (diffSec < 60) return `${diffSec}s ago`
  const diffMin = Math.floor(diffSec / 60)
  if (diffMin < 60) return `${diffMin}m ago`
  const diffHr = Math.floor(diffMin / 60)
  return `${diffHr}h ago`
}

const handleScroll = () => {
  if (!feedContainer.value) return
  const { scrollTop, scrollHeight, clientHeight } = feedContainer.value
  // If user scrolled more than 100px from bottom, disable auto-scroll
  shouldAutoScroll.value = scrollHeight - scrollTop - clientHeight < 100
}

watch(
  () => props.readings.length,
  async () => {
    if (shouldAutoScroll.value && !props.isPaused) {
      await nextTick()
      feedContainer.value?.scrollTo({ top: 0, behavior: 'smooth' })
    }
  }
)
</script>

<template>
  <Card class="h-[calc(100vh-20rem)] flex flex-col">
    <CardHeader class="pb-3">
      <CardTitle class="flex items-center gap-2 text-base">
        <Activity class="h-4 w-4" />
        Activity Feed
        <Badge v-if="!isPaused" variant="outline" class="ml-auto bg-green-500/10 text-green-600">
          Live
        </Badge>
      </CardTitle>
    </CardHeader>
    <CardContent
      ref="feedContainer"
      class="flex-1 overflow-y-auto space-y-2 px-4 scrollbar-thin"
      @scroll="handleScroll"
    >
      <div v-if="readings.length === 0" class="flex items-center justify-center h-full text-muted-foreground text-sm">
        Waiting for readings...
      </div>
      
      <div
        v-for="(reading, index) in readings"
        :key="`${reading.meter_id}-${reading.timestamp}-${index}`"
        class="p-3 rounded-lg border bg-card hover:bg-accent/50 transition-all animate-in fade-in slide-in-from-top-2 duration-300"
        :style="{ animationDelay: `${index * 50}ms` }"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <div class="font-medium text-sm">{{ reading.meter_name }}</div>
              <Badge v-if="reading.is_recent" variant="outline" class="text-xs bg-green-500/10 text-green-600">
                Online
              </Badge>
            </div>
            
            <div class="mt-1 text-lg font-bold">
              {{ reading.power_kw.toFixed(2) }} kW
            </div>
            
            <div class="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
              <span>{{ reading.voltage.avg.toFixed(0) }}V</span>
              <span>•</span>
              <span>{{ reading.current.avg.toFixed(1) }}A</span>
              <span>•</span>
              <span>PF {{ reading.power_factor.toFixed(2) }}</span>
            </div>
          </div>
          
          <div class="text-xs text-muted-foreground text-right whitespace-nowrap">
            {{ formatTimeAgo(reading.timestamp) }}
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background: hsl(var(--muted-foreground) / 0.3);
  border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--muted-foreground) / 0.5);
}
</style>
