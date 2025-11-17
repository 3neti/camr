import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export interface SortConfig {
  column: string | null
  direction: 'asc' | 'desc'
}

export function useSortable(baseUrl: string, initialSort?: SortConfig) {
  const sortColumn = ref<string | null>(initialSort?.column || null)
  const sortDirection = ref<'asc' | 'desc'>(initialSort?.direction || 'asc')

  const isSorted = (column: string) => sortColumn.value === column

  const getSortIcon = (column: string) => {
    if (!isSorted(column)) return null
    return sortDirection.value === 'asc' ? '↑' : '↓'
  }

  const getSortClass = (column: string) => {
    if (!isSorted(column)) return ''
    return 'text-primary font-semibold'
  }

  function sort(column: string, preserveFilters: Record<string, any> = {}) {
    // Toggle direction if clicking same column
    if (sortColumn.value === column) {
      sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortColumn.value = column
      sortDirection.value = 'asc'
    }

    // Navigate with sort params and preserved filters
    router.get(
      baseUrl,
      {
        ...preserveFilters,
        sort: sortColumn.value,
        direction: sortDirection.value,
      },
      {
        preserveState: true,
        preserveScroll: true,
      }
    )
  }

  return {
    sortColumn,
    sortDirection,
    isSorted,
    getSortIcon,
    getSortClass,
    sort,
  }
}
