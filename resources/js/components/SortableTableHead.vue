<script setup lang="ts">
import { TableHead } from '@/components/ui/table'
import { ChevronUp, ChevronDown } from 'lucide-vue-next'
import { unref, Ref } from 'vue'

interface Props {
  column: string
  sortColumn?: string | null | Ref<string | null>
  sortDirection?: 'asc' | 'desc' | Ref<'asc' | 'desc'>
  class?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{ sort: [column: string] }>()

// Unwrap refs to handle both ref and value props
const currentSortColumn = () => unref(props.sortColumn)
const currentSortDirection = () => unref(props.sortDirection)

const isSorted = () => currentSortColumn() === props.column
const getSortIcon = () => {
  if (!isSorted()) return null
  return currentSortDirection() === 'asc' ? ChevronUp : ChevronDown
}
</script>

<template>
  <TableHead :class="['cursor-pointer select-none hover:bg-muted/50 transition-colors', props.class]" @click="emit('sort', column)">
    <div class="flex items-center gap-1">
      <slot />
      <component :is="getSortIcon()" v-if="isSorted()" class="h-4 w-4 text-primary" />
    </div>
  </TableHead>
</template>
