<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card } from '@/components/ui/card'
import { Calendar, X } from 'lucide-vue-next'
import { useDateRange, type DateRangePreset } from '@/composables/useDateRange'

interface Props {
  modelValue?: { start: Date; end: Date }
}

interface Emits {
  (e: 'update:modelValue', value: { start: Date; end: Date }): void
  (e: 'apply'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const dateRange = useDateRange(props.modelValue)

const presets: DateRangePreset[] = [
  'today',
  'yesterday',
  'last7days',
  'last30days',
  'thisMonth',
  'lastMonth',
  'thisYear',
]

function handlePresetChange(preset: DateRangePreset) {
  dateRange.setPreset(preset)
  emitUpdate()
}

function handleDateChange() {
  dateRange.preset.value = 'custom'
  emitUpdate()
}

function emitUpdate() {
  emit('update:modelValue', {
    start: dateRange.startDate.value,
    end: dateRange.endDate.value,
  })
}

function apply() {
  emit('apply')
}

const startDateString = computed({
  get: () => dateRange.formatDate(dateRange.startDate.value),
  set: (value: string) => {
    const date = new Date(value)
    if (!isNaN(date.getTime())) {
      dateRange.startDate.value = date
      handleDateChange()
    }
  },
})

const endDateString = computed({
  get: () => dateRange.formatDate(dateRange.endDate.value),
  set: (value: string) => {
    const date = new Date(value)
    if (!isNaN(date.getTime())) {
      dateRange.endDate.value = date
      handleDateChange()
    }
  },
})
</script>

<template>
  <Card class="p-4 space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Calendar class="h-5 w-5 text-muted-foreground" />
        <h3 class="font-semibold">Date Range</h3>
      </div>
      <span class="text-sm text-muted-foreground">
        {{ dateRange.daysDiff }} days
      </span>
    </div>

    <!-- Quick Presets -->
    <div class="space-y-2">
      <Label>Quick Select</Label>
      <Select :model-value="dateRange.preset.value" @update:model-value="handlePresetChange">
        <SelectTrigger>
          <SelectValue>
            {{ dateRange.getPresetLabel(dateRange.preset.value) }}
          </SelectValue>
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="preset in presets" :key="preset" :value="preset">
            {{ dateRange.getPresetLabel(preset) }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <!-- Custom Date Inputs -->
    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <Label for="start-date">Start Date</Label>
        <Input
          id="start-date"
          v-model="startDateString"
          type="date"
          :max="endDateString"
        />
      </div>
      <div class="space-y-2">
        <Label for="end-date">End Date</Label>
        <Input
          id="end-date"
          v-model="endDateString"
          type="date"
          :min="startDateString"
          :max="dateRange.formatDate(new Date())"
        />
      </div>
    </div>

    <!-- Apply Button -->
    <Button @click="apply" class="w-full">
      Apply Date Range
    </Button>

    <!-- Summary -->
    <div class="text-xs text-muted-foreground text-center">
      {{ dateRange.formatDate(dateRange.startDate.value, 'MMM d, yyyy') }} - 
      {{ dateRange.formatDate(dateRange.endDate.value, 'MMM d, yyyy') }}
    </div>
  </Card>
</template>
