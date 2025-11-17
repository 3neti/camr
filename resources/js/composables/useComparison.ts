import { computed, ref } from 'vue'
import { subMonths, subYears, format, startOfMonth, endOfMonth, startOfYear, endOfYear } from 'date-fns'

export type ComparisonPeriod = 'month' | 'year'

export interface PeriodData {
  label: string
  start: Date
  end: Date
  value?: number
}

export interface ComparisonResult {
  current: PeriodData
  previous: PeriodData
  change: number
  changePercent: number
  trend: 'up' | 'down' | 'flat'
}

export function useComparison(period: ComparisonPeriod = 'month') {
  const comparisonPeriod = ref<ComparisonPeriod>(period)

  /**
   * Get current and previous period dates
   */
  function getPeriods(referenceDate: Date = new Date()): { current: PeriodData; previous: PeriodData } {
    if (comparisonPeriod.value === 'month') {
      const currentStart = startOfMonth(referenceDate)
      const currentEnd = endOfMonth(referenceDate)
      const previousStart = startOfMonth(subMonths(referenceDate, 1))
      const previousEnd = endOfMonth(subMonths(referenceDate, 1))

      return {
        current: {
          label: format(referenceDate, 'MMMM yyyy'),
          start: currentStart,
          end: currentEnd,
        },
        previous: {
          label: format(subMonths(referenceDate, 1), 'MMMM yyyy'),
          start: previousStart,
          end: previousEnd,
        },
      }
    } else {
      const currentStart = startOfYear(referenceDate)
      const currentEnd = endOfYear(referenceDate)
      const previousStart = startOfYear(subYears(referenceDate, 1))
      const previousEnd = endOfYear(subYears(referenceDate, 1))

      return {
        current: {
          label: format(referenceDate, 'yyyy'),
          start: currentStart,
          end: currentEnd,
        },
        previous: {
          label: format(subYears(referenceDate, 1), 'yyyy'),
          start: previousStart,
          end: previousEnd,
        },
      }
    }
  }

  /**
   * Calculate comparison metrics
   */
  function calculateComparison(currentValue: number, previousValue: number): ComparisonResult {
    const periods = getPeriods()
    const change = currentValue - previousValue
    const changePercent = previousValue !== 0 ? (change / previousValue) * 100 : 0

    let trend: 'up' | 'down' | 'flat' = 'flat'
    if (Math.abs(changePercent) >= 1) {
      trend = change > 0 ? 'up' : 'down'
    }

    return {
      current: { ...periods.current, value: currentValue },
      previous: { ...periods.previous, value: previousValue },
      change,
      changePercent,
      trend,
    }
  }

  /**
   * Format comparison for display
   */
  function formatComparison(result: ComparisonResult): string {
    const sign = result.change >= 0 ? '+' : ''
    return `${sign}${result.changePercent.toFixed(1)}%`
  }

  /**
   * Get trend icon/color
   */
  function getTrendColor(trend: 'up' | 'down' | 'flat', inverseLogic = false): string {
    // inverseLogic: true means "up" is bad (e.g., cost increase)
    if (trend === 'flat') return 'text-muted-foreground'
    
    const isGood = inverseLogic ? trend === 'down' : trend === 'up'
    return isGood ? 'text-green-600' : 'text-red-600'
  }

  /**
   * Get multiple comparison periods (for charts)
   */
  function getComparisonSeries(months: number = 12): Array<{ period: string; start: Date; end: Date }> {
    const now = new Date()
    const series: Array<{ period: string; start: Date; end: Date }> = []

    if (comparisonPeriod.value === 'month') {
      for (let i = months - 1; i >= 0; i--) {
        const date = subMonths(now, i)
        series.push({
          period: format(date, 'MMM yyyy'),
          start: startOfMonth(date),
          end: endOfMonth(date),
        })
      }
    } else {
      const years = Math.ceil(months / 12)
      for (let i = years - 1; i >= 0; i--) {
        const date = subYears(now, i)
        series.push({
          period: format(date, 'yyyy'),
          start: startOfYear(date),
          end: endOfYear(date),
        })
      }
    }

    return series
  }

  return {
    comparisonPeriod,
    getPeriods,
    calculateComparison,
    formatComparison,
    getTrendColor,
    getComparisonSeries,
  }
}
