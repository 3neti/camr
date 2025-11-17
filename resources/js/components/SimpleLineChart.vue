<script setup lang="ts">
import { computed } from 'vue'

interface DataPoint {
  label: string
  value: number
}

interface Props {
  data: DataPoint[]
  height?: number
  color?: string
  showDots?: boolean
  showGrid?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  height: 200,
  color: '#3b82f6',
  showDots: true,
  showGrid: true,
})

const padding = { top: 10, right: 10, bottom: 30, left: 50 }
const chartWidth = computed(() => 800 - padding.left - padding.right)
const chartHeight = computed(() => props.height - padding.top - padding.bottom)

const values = computed(() => props.data.map(d => d.value))
const minValue = computed(() => Math.min(...values.value, 0))
const maxValue = computed(() => Math.max(...values.value, 0))
const valueRange = computed(() => maxValue.value - minValue.value || 1)

// Calculate points for the line
const points = computed(() => {
  if (props.data.length === 0) return []
  
  const xStep = chartWidth.value / (props.data.length - 1 || 1)
  
  return props.data.map((point, index) => {
    const x = padding.left + index * xStep
    const y = padding.top + chartHeight.value - 
      ((point.value - minValue.value) / valueRange.value) * chartHeight.value
    
    return { x, y, label: point.label, value: point.value }
  })
})

// Generate path string for the line
const linePath = computed(() => {
  if (points.value.length === 0) return ''
  
  const pathSegments = points.value.map((point, index) => {
    if (index === 0) return `M ${point.x},${point.y}`
    return `L ${point.x},${point.y}`
  })
  
  return pathSegments.join(' ')
})

// Generate path for filled area under the line
const areaPath = computed(() => {
  if (points.value.length === 0) return ''
  
  const firstPoint = points.value[0]
  const lastPoint = points.value[points.value.length - 1]
  
  return `${linePath.value} L ${lastPoint.x},${padding.top + chartHeight.value} L ${firstPoint.x},${padding.top + chartHeight.value} Z`
})

// Y-axis labels
const yAxisLabels = computed(() => {
  const count = 5
  const labels = []
  
  for (let i = 0; i < count; i++) {
    const value = minValue.value + (valueRange.value * i / (count - 1))
    const y = padding.top + chartHeight.value - (i / (count - 1)) * chartHeight.value
    labels.push({ value: value.toFixed(1), y })
  }
  
  return labels.reverse()
})

// X-axis labels (show every nth label to avoid crowding)
const xAxisLabels = computed(() => {
  const maxLabels = 7
  const step = Math.ceil(props.data.length / maxLabels)
  
  return points.value.filter((_, index) => index % step === 0 || index === points.value.length - 1)
})
</script>

<template>
  <div class="w-full overflow-x-auto">
    <svg :width="800" :height="height" class="w-full" style="min-width: 600px">
      <!-- Grid lines -->
      <g v-if="showGrid" opacity="0.1">
        <line
          v-for="label in yAxisLabels"
          :key="label.y"
          :x1="padding.left"
          :y1="label.y"
          :x2="padding.left + chartWidth"
          :y2="label.y"
          stroke="currentColor"
          stroke-width="1"
        />
      </g>
      
      <!-- Area fill -->
      <path
        :d="areaPath"
        :fill="color"
        opacity="0.1"
      />
      
      <!-- Line -->
      <path
        :d="linePath"
        :stroke="color"
        stroke-width="2"
        fill="none"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
      
      <!-- Data points -->
      <g v-if="showDots">
        <circle
          v-for="(point, index) in points"
          :key="index"
          :cx="point.x"
          :cy="point.y"
          r="3"
          :fill="color"
          class="hover:r-5 transition-all"
        >
          <title>{{ point.label }}: {{ point.value }}</title>
        </circle>
      </g>
      
      <!-- Y-axis -->
      <g>
        <line
          :x1="padding.left"
          :y1="padding.top"
          :x2="padding.left"
          :y2="padding.top + chartHeight"
          stroke="currentColor"
          opacity="0.2"
          stroke-width="1"
        />
        <text
          v-for="label in yAxisLabels"
          :key="label.y"
          :x="padding.left - 10"
          :y="label.y"
          text-anchor="end"
          dominant-baseline="middle"
          class="text-xs fill-muted-foreground"
        >
          {{ label.value }}
        </text>
      </g>
      
      <!-- X-axis -->
      <g>
        <line
          :x1="padding.left"
          :y1="padding.top + chartHeight"
          :x2="padding.left + chartWidth"
          :y2="padding.top + chartHeight"
          stroke="currentColor"
          opacity="0.2"
          stroke-width="1"
        />
        <text
          v-for="point in xAxisLabels"
          :key="point.x"
          :x="point.x"
          :y="padding.top + chartHeight + 20"
          text-anchor="middle"
          class="text-xs fill-muted-foreground"
        >
          {{ point.label }}
        </text>
      </g>
    </svg>
  </div>
</template>
