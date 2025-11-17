<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Keyboard, X } from 'lucide-vue-next'

const isOpen = ref(false)

const shortcuts = [
  {
    category: 'Search & Navigation',
    items: [
      { keys: ['⌘', 'K'], description: 'Open search' },
      { keys: ['⌘', '⇧', 'D'], description: 'Go to Dashboard' },
      { keys: ['⌘', '⇧', 'S'], description: 'Go to Sites' },
      { keys: ['⌘', '⇧', 'G'], description: 'Go to Gateways' },
      { keys: ['⌘', '⇧', 'M'], description: 'Go to Meters' },
      { keys: ['⌘', '⇧', 'R'], description: 'Go to Reports' },
    ],
  },
  {
    category: 'Actions',
    items: [
      { keys: ['⌘', 'R'], description: 'Refresh page' },
      { keys: ['⌘', '['], description: 'Go back' },
      { keys: ['⌘', ']'], description: 'Go forward' },
      { keys: ['ESC'], description: 'Close modals' },
    ],
  },
  {
    category: 'Help',
    items: [
      { keys: ['⌘', '/'], description: 'Show keyboard shortcuts' },
    ],
  },
]

function open() {
  isOpen.value = true
}

function close() {
  isOpen.value = false
}

function handleKeydown(e: KeyboardEvent) {
  // ⌘/ or Ctrl/ to open
  if ((e.metaKey || e.ctrlKey) && e.key === '/') {
    e.preventDefault()
    open()
  }
  
  // ESC to close
  if (isOpen.value && e.key === 'Escape') {
    e.preventDefault()
    close()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})

defineExpose({ open, close })
</script>

<template>
  <!-- Backdrop -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 bg-black/50 z-50"
        @click="close"
      />
    </Transition>

    <!-- Modal -->
    <Transition
      enter-active-class="transition-all duration-200"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition-all duration-150"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl z-50 p-4"
      >
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
            <div>
              <CardTitle class="flex items-center gap-2">
                <Keyboard class="h-5 w-5" />
                Keyboard Shortcuts
              </CardTitle>
              <CardDescription>Navigate faster with keyboard shortcuts</CardDescription>
            </div>
            <button
              @click="close"
              class="rounded-lg p-2 hover:bg-accent transition-colors"
            >
              <X class="h-4 w-4" />
            </button>
          </CardHeader>
          <CardContent class="space-y-6">
            <div v-for="section in shortcuts" :key="section.category" class="space-y-3">
              <h3 class="font-semibold text-sm text-muted-foreground">{{ section.category }}</h3>
              <div class="space-y-2">
                <div
                  v-for="(shortcut, index) in section.items"
                  :key="index"
                  class="flex items-center justify-between py-2"
                >
                  <span class="text-sm">{{ shortcut.description }}</span>
                  <div class="flex items-center gap-1">
                    <kbd
                      v-for="(key, keyIndex) in shortcut.keys"
                      :key="keyIndex"
                      class="inline-flex items-center justify-center min-w-[2rem] h-7 px-2 rounded border bg-muted font-mono text-xs font-medium"
                    >
                      {{ key }}
                    </kbd>
                  </div>
                </div>
              </div>
            </div>

            <div class="border-t pt-4">
              <p class="text-xs text-muted-foreground text-center">
                Note: On Windows/Linux, use Ctrl instead of ⌘
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </Transition>
  </Teleport>
</template>
