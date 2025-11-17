<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Search, Building2, Radio, Zap, Users, MapPin, FileCode, Command } from 'lucide-vue-next'
import axios from 'axios'

interface SearchResult {
  type: 'site' | 'gateway' | 'meter' | 'user' | 'location' | 'config_file'
  id: number
  name: string
  subtitle?: string
  url: string
}

const isOpen = ref(false)
const searchQuery = ref('')
const results = ref<SearchResult[]>([])
const selectedIndex = ref(0)
const isLoading = ref(false)

const typeIcons = {
  site: Building2,
  gateway: Radio,
  meter: Zap,
  user: Users,
  location: MapPin,
  config_file: FileCode,
}

const typeColors = {
  site: 'bg-blue-500/10 text-blue-600 border-blue-500/20',
  gateway: 'bg-purple-500/10 text-purple-600 border-purple-500/20',
  meter: 'bg-yellow-500/10 text-yellow-600 border-yellow-500/20',
  user: 'bg-green-500/10 text-green-600 border-green-500/20',
  location: 'bg-pink-500/10 text-pink-600 border-pink-500/20',
  config_file: 'bg-orange-500/10 text-orange-600 border-orange-500/20',
}

const typeLabels = {
  site: 'Site',
  gateway: 'Gateway',
  meter: 'Meter',
  user: 'User',
  location: 'Location',
  config_file: 'Config File',
}

// Debounced search
let searchTimeout: NodeJS.Timeout

watch(searchQuery, async (newQuery) => {
  clearTimeout(searchTimeout)
  
  if (newQuery.length < 2) {
    results.value = []
    return
  }

  isLoading.value = true
  searchTimeout = setTimeout(async () => {
    try {
      const response = await axios.get('/api/search', {
        params: { q: newQuery }
      })
      results.value = response.data.results
      selectedIndex.value = 0
    } catch (error) {
      console.error('Search error:', error)
      results.value = []
    } finally {
      isLoading.value = false
    }
  }, 300)
})

function open() {
  isOpen.value = true
  searchQuery.value = ''
  results.value = []
  selectedIndex.value = 0
}

function close() {
  isOpen.value = false
  searchQuery.value = ''
  results.value = []
}

function selectResult(index: number) {
  const result = results.value[index]
  if (result) {
    router.visit(result.url)
    close()
  }
}

function handleKeydown(e: KeyboardEvent) {
  if (!isOpen.value) {
    // Cmd+K or Ctrl+K to open
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault()
      open()
    }
    return
  }

  // Navigation within search
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    selectedIndex.value = Math.min(selectedIndex.value + 1, results.value.length - 1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    selectedIndex.value = Math.max(selectedIndex.value - 1, 0)
  } else if (e.key === 'Enter') {
    e.preventDefault()
    selectResult(selectedIndex.value)
  } else if (e.key === 'Escape') {
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

    <!-- Search Modal -->
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
        class="fixed top-[10%] left-1/2 -translate-x-1/2 w-full max-w-2xl z-50 p-4"
      >
        <div class="bg-background border rounded-lg shadow-2xl overflow-hidden">
          <!-- Search Input -->
          <div class="flex items-center gap-3 px-4 py-3 border-b">
            <Search class="h-5 w-5 text-muted-foreground" />
            <Input
              v-model="searchQuery"
              placeholder="Search sites, gateways, meters, users..."
              class="flex-1 border-0 focus-visible:ring-0 shadow-none"
              autofocus
            />
            <kbd class="hidden sm:inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground">
              <span class="text-xs">ESC</span>
            </kbd>
          </div>

          <!-- Results -->
          <div class="max-h-[60vh] overflow-y-auto">
            <!-- Loading -->
            <div v-if="isLoading" class="p-8 text-center text-muted-foreground">
              <div class="animate-spin h-8 w-8 border-2 border-primary border-t-transparent rounded-full mx-auto mb-2" />
              <p class="text-sm">Searching...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="searchQuery.length >= 2 && results.length === 0" class="p-8 text-center text-muted-foreground">
              <Search class="h-12 w-12 mx-auto mb-3 opacity-50" />
              <p class="text-sm">No results found for "{{ searchQuery }}"</p>
            </div>

            <!-- Help Text -->
            <div v-else-if="searchQuery.length < 2" class="p-8 text-center text-muted-foreground">
              <Command class="h-12 w-12 mx-auto mb-3 opacity-50" />
              <p class="text-sm">Type at least 2 characters to search</p>
              <p class="text-xs mt-2 opacity-75">Search across sites, gateways, meters, users, and more</p>
            </div>

            <!-- Results List -->
            <div v-else class="py-2">
              <button
                v-for="(result, index) in results"
                :key="`${result.type}-${result.id}`"
                :class="[
                  'w-full flex items-center gap-3 px-4 py-3 text-left transition-colors',
                  selectedIndex === index ? 'bg-accent' : 'hover:bg-accent/50'
                ]"
                @click="selectResult(index)"
                @mouseenter="selectedIndex = index"
              >
                <div :class="['flex items-center justify-center w-10 h-10 rounded-lg', typeColors[result.type]]">
                  <component :is="typeIcons[result.type]" class="h-5 w-5" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="font-medium truncate">{{ result.name }}</div>
                  <div v-if="result.subtitle" class="text-xs text-muted-foreground truncate">
                    {{ result.subtitle }}
                  </div>
                </div>
                <Badge variant="outline" class="text-xs">
                  {{ typeLabels[result.type] }}
                </Badge>
              </button>
            </div>
          </div>

          <!-- Footer -->
          <div class="border-t px-4 py-2 bg-muted/50 text-xs text-muted-foreground flex items-center justify-between">
            <div class="flex items-center gap-4">
              <span class="flex items-center gap-1">
                <kbd class="px-1.5 py-0.5 rounded bg-background border">↑↓</kbd>
                Navigate
              </span>
              <span class="flex items-center gap-1">
                <kbd class="px-1.5 py-0.5 rounded bg-background border">Enter</kbd>
                Select
              </span>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1">
              <kbd class="px-1.5 py-0.5 rounded bg-background border font-mono">⌘K</kbd>
              to open
            </span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
