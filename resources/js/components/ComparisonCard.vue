<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { TrendingUp, TrendingDown, Minus } from 'lucide-vue-next'
import { computed } from 'vue'

interface Props {
  title: string
  currentLabel: string
  previousLabel: string
  currentValue: number
  previousValue: number
  unit?: string
  format?: 'number' | 'energy' | 'power'
  inverseLogic?: boolean // true if increase is bad (e.g., cost)
}

const props = withDefaults(defineProps<Props>(), {
  unit: '',
  format: 'number',
  inverseLogic: false,
})

const change = computed(() => props.currentValue - props.previousValue)
const changePercent = computed(() => 
  props.previousValue !== 0 ? (change.value / props.previousValue) * 100 : 0
)
const trend = computed(() => {
  if (Math.abs(changePercent.value) < 1) return 'flat'
  return change.value > 0 ? 'up' : 'down'
})

const trendColor = computed(() => {
  if (trend.value === 'flat') return 'text-muted-foreground'
  const isGood = props.inverseLogic ? trend.value === 'down' : trend.value === 'up'
  return isGood ? 'text-green-600' : 'text-red-600'
})

const trendBadgeVariant = computed(() => {
  if (trend.value === 'flat') return 'outline'
  const isGood = props.inverseLogic ? trend.value === 'down' : trend.value === 'up'
  return isGood ? 'default' : 'destructive'
})

const TrendIcon = computed(() => {
  if (trend.value === 'flat') return Minus
  return trend.value === 'up' ? TrendingUp : TrendingDown
})

function formatValue(value: number): string {
  if (props.format === 'energy') {
    return `${(value / 1000).toFixed(2)} kWh`
  } else if (props.format === 'power') {
    return `${(value / 1000).toFixed(2)} kW`
  } else {
    return value.toLocaleString()
  }
}

function formatChange(): string {
  const sign = change.value >= 0 ? '+' : ''
  return `${sign}${changePercent.value.toFixed(1)}%`
}
</script>

<template>
  <Card>
    <CardHeader class="pb-3">
      <CardTitle class="text-sm font-medium">{{ title }}</CardTitle>
      <CardDescription>Period comparison</CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <!-- Current Period -->
      <div>
        <div class="text-xs text-muted-foreground mb-1">{{ currentLabel }}</div>
        <div class="text-2xl font-bold">{{ formatValue(currentValue) }}</div>
      </div>

      <!-- Previous Period -->
      <div>
        <div class="text-xs text-muted-foreground mb-1">{{ previousLabel }}</div>
        <div class="text-lg font-semibold text-muted-foreground">{{ formatValue(previousValue) }}</div>
      </div>

      <!-- Change Indicator -->
      <div class="flex items-center justify-between pt-2 border-t">
        <div class="flex items-center gap-2">
          <component :is="TrendIcon" :class="['h-4 w-4', trendColor]" />
          <span :class="['text-sm font-medium', trendColor]">
            {{ formatChange() }}
          </span>
        </div>
        <Badge :variant="trendBadgeVariant" class="text-xs">
          {{ trend === 'up' ? 'Higher' : trend === 'down' ? 'Lower' : 'Similar' }}
        </Badge>
      </div>

      <!-- Absolute Change -->
      <div class="text-xs text-muted-foreground">
        {{ change >= 0 ? '+' : '' }}{{ formatValue(Math.abs(change)) }} vs previous period
      </div>
    </CardContent>
  </Card>
</template>
