import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export function useBulkActions<T extends { id: number }>(items: T[]) {
  const selectedIds = ref<number[]>([])

  const allSelected = computed({
    get: () => items.length > 0 && selectedIds.value.length === items.length,
    set: (value: boolean) => {
      selectedIds.value = value ? items.map(item => item.id) : []
    }
  })

  const someSelected = computed(() => 
    selectedIds.value.length > 0 && selectedIds.value.length < items.length
  )

  const hasSelection = computed(() => selectedIds.value.length > 0)

  function toggleSelection(id: number) {
    const index = selectedIds.value.indexOf(id)
    if (index > -1) {
      selectedIds.value.splice(index, 1)
    } else {
      selectedIds.value.push(id)
    }
  }

  function isSelected(id: number) {
    return selectedIds.value.includes(id)
  }

  function clearSelection() {
    selectedIds.value = []
  }

  function bulkDelete(url: string, onSuccess?: () => void) {
    if (selectedIds.value.length === 0) return

    if (confirm(`Are you sure you want to delete ${selectedIds.value.length} items?`)) {
      router.delete(url, {
        data: { ids: selectedIds.value },
        onSuccess: () => {
          clearSelection()
          onSuccess?.()
        },
      })
    }
  }

  return {
    selectedIds,
    allSelected,
    someSelected,
    hasSelection,
    toggleSelection,
    isSelected,
    clearSelection,
    bulkDelete,
  }
}
