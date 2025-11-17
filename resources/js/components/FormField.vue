<script setup lang="ts">
import { Label } from '@/components/ui/label'
import { AlertCircle, CheckCircle2 } from 'lucide-vue-next'

interface Props {
  label: string
  name: string
  error?: string
  hint?: string
  required?: boolean
  showSuccess?: boolean
}

const props = defineProps<Props>()
</script>

<template>
  <div class="space-y-2">
    <Label :for="props.name" class="flex items-center gap-1">
      {{ props.label }}
      <span v-if="props.required" class="text-destructive">*</span>
    </Label>
    
    <slot />
    
    <!-- Error Message -->
    <div v-if="props.error" class="flex items-center gap-1.5 text-sm text-destructive">
      <AlertCircle class="h-4 w-4" />
      <span>{{ props.error }}</span>
    </div>
    
    <!-- Success Indicator (optional) -->
    <div v-else-if="props.showSuccess" class="flex items-center gap-1.5 text-sm text-green-600">
      <CheckCircle2 class="h-4 w-4" />
      <span>Valid</span>
    </div>
    
    <!-- Hint Text -->
    <p v-else-if="props.hint" class="text-sm text-muted-foreground">
      {{ props.hint }}
    </p>
  </div>
</template>
