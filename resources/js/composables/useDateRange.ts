import { ref, computed } from 'vue'
import { format, subDays, subMonths, startOfMonth, endOfMonth, startOfYear, endOfYear } from 'date-fns'

export type DateRangePreset = 'today' | 'yesterday' | 'last7days' | 'last30days' | 'thisMonth' | 'lastMonth' | 'thisYear' | 'custom'

export interface DateRange {
  start: Date
  end: Date
}

export function useDateRange(initialRange?: DateRange) {
  const today = new Date()
  
  const startDate = ref<Date>(initialRange?.start || subDays(today, 7))
  const endDate = ref<Date>(initialRange?.end || today)
  const preset = ref<DateRangePreset>('last7days')

  const dateRange = computed<DateRange>(() => ({
    start: startDate.value,
    end: endDate.value,
  }))

  const daysDiff = computed(() => {
    const diff = endDate.value.getTime() - startDate.value.getTime()
    return Math.ceil(diff / (1000 * 60 * 60 * 24))
  })

  function setPreset(presetName: DateRangePreset) {
    preset.value = presetName
    const now = new Date()

    switch (presetName) {
      case 'today':
        startDate.value = new Date(now.setHours(0, 0, 0, 0))
        endDate.value = new Date()
        break
      case 'yesterday':
        const yesterday = subDays(now, 1)
        startDate.value = new Date(yesterday.setHours(0, 0, 0, 0))
        endDate.value = new Date(yesterday.setHours(23, 59, 59, 999))
        break
      case 'last7days':
        startDate.value = subDays(now, 7)
        endDate.value = now
        break
      case 'last30days':
        startDate.value = subDays(now, 30)
        endDate.value = now
        break
      case 'thisMonth':
        startDate.value = startOfMonth(now)
        endDate.value = endOfMonth(now)
        break
      case 'lastMonth':
        const lastMonth = subMonths(now, 1)
        startDate.value = startOfMonth(lastMonth)
        endDate.value = endOfMonth(lastMonth)
        break
      case 'thisYear':
        startDate.value = startOfYear(now)
        endDate.value = endOfYear(now)
        break
      case 'custom':
        // Keep current dates
        break
    }
  }

  function setCustomRange(start: Date, end: Date) {
    preset.value = 'custom'
    startDate.value = start
    endDate.value = end
  }

  function formatDate(date: Date, formatString = 'yyyy-MM-dd'): string {
    return format(date, formatString)
  }

  function getFormattedRange(): { start: string; end: string } {
    return {
      start: formatDate(startDate.value),
      end: formatDate(endDate.value),
    }
  }

  function getPresetLabel(presetName: DateRangePreset): string {
    const labels: Record<DateRangePreset, string> = {
      today: 'Today',
      yesterday: 'Yesterday',
      last7days: 'Last 7 Days',
      last30days: 'Last 30 Days',
      thisMonth: 'This Month',
      lastMonth: 'Last Month',
      thisYear: 'This Year',
      custom: 'Custom Range',
    }
    return labels[presetName]
  }

  return {
    startDate,
    endDate,
    preset,
    dateRange,
    daysDiff,
    setPreset,
    setCustomRange,
    formatDate,
    getFormattedRange,
    getPresetLabel,
  }
}
