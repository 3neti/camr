<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
  type ChartOptions,
} from 'chart.js'
import { Line } from 'vue-chartjs'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

interface Props {
  labels: string[]
  datasets: Array<{
    label: string
    data: number[]
    borderColor?: string
    backgroundColor?: string
    fill?: boolean
    tension?: number
  }>
  title?: string
  yAxisLabel?: string
  height?: number
}

const props = withDefaults(defineProps<Props>(), {
  height: 300,
  title: '',
  yAxisLabel: '',
})

const chartData = computed(() => ({
  labels: props.labels,
  datasets: props.datasets.map((dataset, index) => ({
    ...dataset,
    borderColor: dataset.borderColor || getDefaultColor(index),
    backgroundColor: dataset.backgroundColor || getDefaultColor(index, 0.1),
    fill: dataset.fill ?? false,
    tension: dataset.tension ?? 0.4,
  })),
}))

const chartOptions = computed<ChartOptions<'line'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'top',
    },
    title: {
      display: !!props.title,
      text: props.title,
    },
    tooltip: {
      mode: 'index',
      intersect: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      title: {
        display: !!props.yAxisLabel,
        text: props.yAxisLabel,
      },
    },
    x: {
      title: {
        display: false,
      },
    },
  },
  interaction: {
    mode: 'nearest',
    axis: 'x',
    intersect: false,
  },
}))

function getDefaultColor(index: number, alpha: number = 1): string {
  const colors = [
    `rgba(59, 130, 246, ${alpha})`, // blue
    `rgba(34, 197, 94, ${alpha})`,  // green
    `rgba(249, 115, 22, ${alpha})`, // orange
    `rgba(239, 68, 68, ${alpha})`,  // red
    `rgba(168, 85, 247, ${alpha})`, // purple
    `rgba(236, 72, 153, ${alpha})`, // pink
  ]
  return colors[index % colors.length]
}
</script>

<template>
  <div :style="{ height: `${height}px` }">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
