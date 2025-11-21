<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Upload, FileText, AlertCircle, CheckCircle, Clock, Trash2 } from 'lucide-vue-next'

interface DataImport {
  id: number
  filename: string
  status: 'uploading' | 'queued' | 'processing' | 'completed' | 'failed'
  progress: {
    current?: number
    total?: number
  }
  statistics?: {
    sites?: number
    users?: number
    gateways?: number
    meters?: number
    meter_data?: number
  }
  error_message?: string
  started_at?: string
  completed_at?: string
  created_at: string
}

interface Props {
  imports: {
    data: DataImport[]
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

const props = defineProps<Props>()

const dragover = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const uploading = ref(false)
const uploadProgress = ref(0)
const statusPolling = ref<NodeJS.Timeout | null>(null)
const activeImports = ref<Map<number, DataImport>>(new Map())

onMounted(() => {
  // Initialize active imports from props
  props.imports.data.forEach(imp => {
    if (['uploading', 'queued', 'processing'].includes(imp.status)) {
      activeImports.value.set(imp.id, imp)
    }
  })

  // Start polling for status updates
  if (activeImports.value.size > 0) {
    startStatusPolling()
  }
})

onBeforeUnmount(() => {
  stopStatusPolling()
})

function startStatusPolling() {
  statusPolling.value = setInterval(() => {
    activeImports.value.forEach((imp) => {
      fetch(`/admin/data-import/${imp.id}/status`)
        .then(res => res.json())
        .then(data => {
          activeImports.value.set(imp.id, data)
          
          // Remove from active if completed or failed
          if (!['uploading', 'queued', 'processing'].includes(data.status)) {
            activeImports.value.delete(imp.id)
            if (activeImports.value.size === 0) {
              stopStatusPolling()
              // Reload imports list
              router.reload()
            }
          }
        })
        .catch(err => console.error('Status polling error:', err))
    })
  }, 2000)
}

function stopStatusPolling() {
  if (statusPolling.value) {
    clearInterval(statusPolling.value)
    statusPolling.value = null
  }
}

function handleDragover(e: DragEvent) {
  e.preventDefault()
  dragover.value = true
}

function handleDragleave() {
  dragover.value = false
}

function handleDrop(e: DragEvent) {
  e.preventDefault()
  dragover.value = false
  const files = e.dataTransfer?.files
  if (files?.length) {
    handleFiles(files)
  }
}

function handleFileSelect(e: Event) {
  const files = (e.target as HTMLInputElement).files
  if (files?.length) {
    handleFiles(files)
  }
}

function handleFiles(files: FileList) {
  const file = files[0]
  if (!file) return

  // Validate file type
  if (!file.name.endsWith('.sql')) {
    alert('Please upload a .sql file')
    return
  }

  // Validate file size (50MB)
  if (file.size > 50 * 1024 * 1024) {
    alert('File size must be less than 50MB')
    return
  }

  uploadFile(file)
}

function uploadFile(file: File) {
  uploading.value = true
  uploadProgress.value = 0

  const formData = new FormData()
  formData.append('file', file)

  // Get CSRF token from meta tag (set by Blade)
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  
  fetch('/admin/data-import/upload', {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': token,
    },
  })
    .then(res => res.json().then(data => ({ ok: res.ok, status: res.status, data })))
    .then(({ ok, status, data }) => {
      if (!ok) {
        let errorMsg = `HTTP ${status}`
        if (status === 422 && data.errors) {
          // Validation errors
          errorMsg = Object.values(data.errors).flat().join(', ')
        } else if (data.message) {
          errorMsg = data.message
        }
        throw new Error(errorMsg)
      }
      if (data.success) {
        // Add to active imports
        const newImport = {
          id: data.import_id,
          filename: file.name,
          status: 'queued',
          progress: {},
          created_at: new Date().toISOString(),
        }
        activeImports.value.set(data.import_id, newImport as DataImport)
        if (activeImports.value.size === 1) {
          startStatusPolling()
        }
        if (fileInput.value) {
          fileInput.value.value = ''
        }
      }
    })
    .catch(error => {
      console.error('Upload error:', error)
      alert('Upload failed: ' + error.message)
    })
    .finally(() => {
      uploading.value = false
      uploadProgress.value = 0
    })
}

function getStatusBadge(status: string) {
  switch (status) {
    case 'uploading':
    case 'queued':
    case 'processing':
      return { variant: 'secondary', icon: Clock, label: status.charAt(0).toUpperCase() + status.slice(1) }
    case 'completed':
      return { variant: 'default', icon: CheckCircle, label: 'Completed' }
    case 'failed':
      return { variant: 'destructive', icon: AlertCircle, label: 'Failed' }
    default:
      return { variant: 'outline', icon: FileText, label: status }
  }
}

function getProgressPercentage(imp: DataImport): number {
  if (imp.progress?.current && imp.progress?.total) {
    return Math.round((imp.progress.current / imp.progress.total) * 100)
  }
  return 0
}

function cancelImport(imp: DataImport) {
  if (confirm(`Cancel import "${imp.filename}"?`)) {
    router.delete(`/admin/data-import/${imp.id}`, {
      preserveState: true,
      onSuccess: () => {
        router.reload()
      },
    })
  }
}

function formatDate(date: string): string {
  return new Date(date).toLocaleString()
}

const allImports = computed(() => {
  const activeList = Array.from(activeImports.value.values())
  const inactiveList = props.imports.data.filter(
    imp => ! ['uploading', 'queued', 'processing'].includes(imp.status)
  )
  return [...activeList, ...inactiveList]
})
</script>

<template>
  <Head title="Data Import" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
          <Upload class="h-8 w-8" />
          Data Import
        </h1>
        <p class="text-muted-foreground">Upload and manage SQL dump imports</p>
      </div>

      <!-- Upload Card -->
      <Card>
        <CardHeader>
          <CardTitle>Upload SQL Dump</CardTitle>
          <CardDescription>Upload a .sql file to import meter data and configuration</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Drag & Drop Zone -->
          <div
            @dragover="handleDragover"
            @dragleave="handleDragleave"
            @drop="handleDrop"
            :class="{
              'border-2 border-dashed rounded-lg p-8 transition-colors': true,
              'border-primary bg-primary/5': dragover,
              'border-muted-foreground/25 bg-muted/30': !dragover,
            }"
          >
            <div class="flex flex-col items-center justify-center space-y-4">
              <Upload :class="['h-12 w-12 text-muted-foreground', dragover && 'text-primary']" />
              <div class="text-center">
                <p class="font-medium">
                  <span v-if="dragover">Drop your file here</span>
                  <span v-else>Drag and drop your SQL file here, or click to select</span>
                </p>
                <p class="text-sm text-muted-foreground mt-1">
                  Maximum file size: 50MB
                </p>
              </div>
              <Button
                :disabled="uploading"
                @click="fileInput?.click()"
              >
                Select File
              </Button>
              <input
                ref="fileInput"
                type="file"
                accept=".sql"
                class="hidden"
                @change="handleFileSelect"
              />
            </div>
          </div>

          <!-- Upload Progress -->
          <div v-if="uploading" class="space-y-2">
            <div class="flex justify-between items-center text-sm">
              <span>Uploading...</span>
              <span class="font-medium">{{ uploadProgress }}%</span>
            </div>
            <div class="w-full bg-secondary rounded-full h-2 overflow-hidden">
              <div
                class="bg-primary h-full transition-all duration-300"
                :style="{ width: `${uploadProgress}%` }"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Active Imports -->
      <div v-if="Array.from(activeImports.values()).length > 0" class="space-y-4">
        <h2 class="text-xl font-semibold">Active Imports</h2>
        <div class="grid gap-4">
          <Card v-for="imp in Array.from(activeImports.values())" :key="imp.id">
            <CardContent class="pt-6">
              <div class="space-y-4">
                <!-- Status and filename -->
                <div class="flex items-start justify-between">
                  <div class="space-y-1">
                    <p class="font-medium flex items-center gap-2">
                      <FileText class="h-4 w-4" />
                      {{ imp.filename }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                      Started {{ formatDate(imp.started_at || imp.created_at) }}
                    </p>
                  </div>
                  <Badge :variant="getStatusBadge(imp.status).variant" class="capitalize">
                    {{ getStatusBadge(imp.status).label }}
                  </Badge>
                </div>

                <!-- Progress bar -->
                <div class="space-y-2">
                  <div class="flex justify-between items-center text-sm">
                    <span v-if="imp.progress?.current">
                      {{ imp.progress.current }} / {{ imp.progress.total }} records
                    </span>
                    <span class="font-medium">{{ getProgressPercentage(imp) }}%</span>
                  </div>
                  <div class="w-full bg-secondary rounded-full h-2 overflow-hidden">
                    <div
                      class="bg-primary h-full transition-all duration-300"
                      :style="{ width: `${getProgressPercentage(imp)}%` }"
                    />
                  </div>
                </div>

                <!-- Statistics -->
                <div v-if="imp.statistics" class="grid grid-cols-5 gap-2 text-sm">
                  <div class="bg-muted rounded p-2 text-center">
                    <p class="text-muted-foreground">Sites</p>
                    <p class="font-medium">{{ imp.statistics.sites || 0 }}</p>
                  </div>
                  <div class="bg-muted rounded p-2 text-center">
                    <p class="text-muted-foreground">Users</p>
                    <p class="font-medium">{{ imp.statistics.users || 0 }}</p>
                  </div>
                  <div class="bg-muted rounded p-2 text-center">
                    <p class="text-muted-foreground">Gateways</p>
                    <p class="font-medium">{{ imp.statistics.gateways || 0 }}</p>
                  </div>
                  <div class="bg-muted rounded p-2 text-center">
                    <p class="text-muted-foreground">Meters</p>
                    <p class="font-medium">{{ imp.statistics.meters || 0 }}</p>
                  </div>
                  <div class="bg-muted rounded p-2 text-center">
                    <p class="text-muted-foreground">Readings</p>
                    <p class="font-medium">{{ imp.statistics.meter_data || 0 }}</p>
                  </div>
                </div>

                <!-- Error message -->
                <Alert v-if="imp.error_message" variant="destructive">
                  <AlertCircle class="h-4 w-4" />
                  <AlertDescription>{{ imp.error_message }}</AlertDescription>
                </Alert>

                <!-- Cancel button -->
                <div class="flex gap-2 justify-end">
                  <Button
                    variant="outline"
                    size="sm"
                    @click="cancelImport(imp)"
                    :disabled="!['queued', 'processing'].includes(imp.status)"
                  >
                    <Trash2 class="h-4 w-4 mr-2" />
                    Cancel
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Import History -->
      <Card>
        <CardHeader>
          <CardTitle>Import History</CardTitle>
          <CardDescription>
            Recent imports ({{ allImports.length }} total)
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Filename</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Progress</TableHead>
                <TableHead>Data Imported</TableHead>
                <TableHead>Date</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="allImports.length === 0">
                <TableCell colspan="6" class="text-center text-muted-foreground">
                  No imports yet
                </TableCell>
              </TableRow>
              <TableRow v-for="imp in allImports" :key="imp.id">
                <TableCell class="font-medium flex items-center gap-2">
                  <FileText class="h-4 w-4 text-muted-foreground" />
                  {{ imp.filename }}
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadge(imp.status).variant" class="capitalize">
                    {{ getStatusBadge(imp.status).label }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div class="flex items-center gap-2">
                    <div class="w-24 bg-secondary rounded-full h-2 overflow-hidden">
                      <div
                        class="bg-primary h-full"
                        :style="{ width: `${getProgressPercentage(imp)}%` }"
                      />
                    </div>
                    <span class="text-sm">{{ getProgressPercentage(imp) }}%</span>
                  </div>
                </TableCell>
                <TableCell class="text-sm">
                  <span v-if="imp.statistics">
                    {{ imp.statistics.sites || 0 }}S / {{ imp.statistics.meters || 0 }}M / {{ imp.statistics.meter_data || 0 }}R
                  </span>
                  <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell class="text-sm text-muted-foreground">
                  {{ formatDate(imp.completed_at || imp.created_at) }}
                </TableCell>
                <TableCell class="text-right">
                  <Button
                    v-if="['queued', 'processing'].includes(imp.status)"
                    variant="ghost"
                    size="sm"
                    @click="cancelImport(imp)"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
