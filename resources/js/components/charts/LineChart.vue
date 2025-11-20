<script setup lang="ts">
import { computed } from 'vue'
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

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

interface Props {
  labels?: string[]
  datasets?: any[]
  data?: any
  options?: any
  height?: number
  yAxisLabel?: string
}

const props = defineProps<Props>()

// Support both old API (data prop) and new API (labels + datasets)
const chartData = computed(() => {
  if (props.data) {
    return props.data
  }
  return {
    labels: props.labels || [],
    datasets: props.datasets || []
  }
})

const chartOptions = computed(() => {
  return props.options || {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: true
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        title: {
          display: !!props.yAxisLabel,
          text: props.yAxisLabel
        }
      }
    }
  }
})
</script>

<template>
  <div :style="{ height: height ? `${height}px` : '300px' }">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
