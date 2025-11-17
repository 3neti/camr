<script setup lang="ts">
import { computed } from 'vue'
import { CheckIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{
  checked?: boolean
  disabled?: boolean
  id?: string
}>()

const emit = defineEmits<{
  'update:checked': [value: boolean]
}>()

const isChecked = computed({
  get: () => props.checked,
  set: (value) => emit('update:checked', value),
})

const toggle = () => {
  if (!props.disabled) {
    isChecked.value = !isChecked.value
  }
}
</script>

<template>
  <button
    type="button"
    role="checkbox"
    :aria-checked="isChecked"
    :disabled="disabled"
    :class="cn(
      'peer h-4 w-4 shrink-0 rounded-sm border border-primary ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
      isChecked ? 'bg-primary text-primary-foreground' : 'bg-background',
    )"
    @click="toggle"
  >
    <CheckIcon v-if="isChecked" class="h-4 w-4" />
  </button>
</template>
