<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Checkbox } from '@/components/ui/checkbox'
import { Upload, FileText, Database, CheckCircle, XCircle, Loader2, FileArchive } from 'lucide-vue-next'
import axios from 'axios'

interface UploadedFile {
  path: string
  filename: string
  type: 'sql' | 'csv'
  size: number
  info: any
}

const sqlFile = ref<UploadedFile | null>(null)
const csvFiles = ref<UploadedFile[]>([])
const uploading = ref(false)
const importing = ref(false)
const importJobs = ref<any[]>([])
const error = ref('')
const success = ref('')

// Import options
const options = ref({
  create_missing_meters: true,
  update_timestamps: true,
  clear_existing: false,
})

// Handle SQL file upload
async function handleSqlUpload(event: Event) {
  const input = event.target as HTMLInputElement
  if (!input.files?.length) return

  const file = input.files[0]
  await uploadFile(file, 'sql')
}

// Handle CSV files upload
async function handleCsvUpload(event: Event) {
  const input = event.target as HTMLInputElement
  if (!input.files?.length) return

  for (const file of Array.from(input.files)) {
    await uploadFile(file, 'csv')
  }
}

// Handle Zip file upload
async function handleZipUpload(event: Event) {
  const input = event.target as HTMLInputElement
  if (!input.files?.length) return

  const file = input.files[0]
  await uploadFile(file, 'zip')
}

// Upload file to server
async function uploadFile(file: File, type: 'sql' | 'csv' | 'zip') {
  uploading.value = true
  error.value = ''
  success.value = ''

  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('type', type)

    const response = await axios.post('/settings/data-import/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    // Check for validation errors (422 status)
    if (!response.data.success && response.data.errors) {
      error.value = response.data.message || 'File validation failed'
      
      // Add detailed error messages
      if (response.data.errors.length > 0) {
        error.value += ':\n' + response.data.errors.join('\n')
      }
      
      // Show warnings too
      if (response.data.warnings && response.data.warnings.length > 0) {
        success.value = 'Warnings:\n' + response.data.warnings.join('\n')
      }
      return
    }

    // Handle zip file response (contains multiple files)
    if (type === 'zip' && response.data.files) {
      for (const extractedFile of response.data.files) {
        if (extractedFile.type === 'sql') {
          sqlFile.value = extractedFile
        } else if (extractedFile.type === 'csv') {
          csvFiles.value.push(extractedFile)
        }
      }
      success.value = response.data.message
    } else {
      // Handle single file response
      const uploadedFile: UploadedFile = {
        path: response.data.path,
        filename: response.data.filename,
        type: type as 'sql' | 'csv',
        size: response.data.size,
        info: response.data.info,
      }

      if (type === 'sql') {
        sqlFile.value = uploadedFile
      } else {
        csvFiles.value.push(uploadedFile)
      }

      success.value = response.data.message || `${file.name} uploaded successfully`
      
      // Show file statistics and warnings
      if (response.data.statistics) {
        const stats = response.data.statistics
        const statsText = `\nFile validated:\n  Tables found: ${stats.tables_found || 0}\n  Sites: ${stats.meter_site_rows || 0}\n  Meters: ${stats.meter_details_rows || 0}\n  Users: ${stats.user_tb_rows || 0}\n  Meter data: ${stats.meter_data_rows || 0}`
        success.value += statsText
      }
      
      if (response.data.warnings && response.data.warnings.length > 0) {
        success.value += '\n\nWarnings:\n' + response.data.warnings.join('\n')
      }
    }
  } catch (err: any) {
    if (err.response?.status === 422) {
      // Validation failed
      const data = err.response.data
      error.value = data.message || 'File validation failed'
      if (data.errors && data.errors.length > 0) {
        error.value += ':\n• ' + data.errors.join('\n• ')
      }
    } else {
      error.value = err.response?.data?.error || 'Upload failed'
    }
  } finally {
    uploading.value = false
  }
}

// Start import process
async function startImport() {
  if (!sqlFile.value && csvFiles.value.length === 0) {
    error.value = 'Please upload at least one file'
    return
  }

  importing.value = true
  error.value = ''
  success.value = ''

  try {
    const files = []
    if (sqlFile.value) files.push(sqlFile.value)
    files.push(...csvFiles.value)

    const response = await axios.post('/settings/data-import/import', {
      files,
      options: options.value,
    })

    importJobs.value = response.data.jobs
    success.value = response.data.message

    // Start polling for progress
    startProgressPolling()
  } catch (err: any) {
    error.value = err.response?.data?.error || 'Import failed'
    importing.value = false
  }
}

// Poll for import progress
function startProgressPolling() {
  const interval = setInterval(async () => {
    try {
      const jobIds = importJobs.value.map(j => j.id)
      const response = await axios.post('/settings/data-import/progress', { job_ids: jobIds })

      importJobs.value = response.data.jobs

      // Check if all jobs are complete
      const allDone = importJobs.value.every(j =>
        ['completed', 'failed', 'cancelled'].includes(j.status)
      )

      if (allDone) {
        clearInterval(interval)
        importing.value = false
      }
    } catch (err) {
      clearInterval(interval)
      importing.value = false
    }
  }, 2000) // Poll every 2 seconds
}

// Remove uploaded file
function removeFile(type: 'sql' | 'csv', index?: number) {
  if (type === 'sql') {
    sqlFile.value = null
  } else if (index !== undefined) {
    csvFiles.value.splice(index, 1)
  }
}

// Format file size
function formatSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

// Get status color
function getStatusColor(status: string) {
  return {
    pending: 'text-gray-500',
    processing: 'text-blue-500',
    completed: 'text-green-500',
    failed: 'text-red-500',
    cancelled: 'text-orange-500',
  }[status] || 'text-gray-500'
}

// Get status icon
function getStatusIcon(status: string) {
  return {
    pending: Loader2,
    processing: Loader2,
    completed: CheckCircle,
    failed: XCircle,
    cancelled: XCircle,
  }[status] || Loader2
}
</script>

<template>
  <Head title="Data Import" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6 max-w-4xl">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Data Import</h1>
        <p class="text-muted-foreground">Upload SQL dumps and CSV files to import meter data</p>
      </div>

      <!-- Alerts -->
      <Alert v-if="error" variant="destructive">
        <AlertDescription>{{ error }}</AlertDescription>
      </Alert>

      <Alert v-if="success" class="bg-green-50 border-green-200">
        <AlertDescription class="text-green-800">{{ success }}</AlertDescription>
      </Alert>

      <!-- Upload Section -->
      <div class="grid gap-6 md:grid-cols-3">
        <!-- Zip Upload -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <FileArchive class="h-5 w-5" />
              Zip Archive
            </CardTitle>
            <CardDescription>Upload zip file containing SQL/CSV</CardDescription>
          </CardHeader>
          <CardContent>
            <label class="flex flex-col items-center justify-center h-32 border-2 border-dashed rounded-lg cursor-pointer hover:bg-muted/50">
              <Upload class="h-8 w-8 text-muted-foreground" />
              <span class="mt-2 text-sm text-muted-foreground">Click to upload ZIP</span>
              <input type="file" accept=".zip" class="hidden" @change="handleZipUpload" :disabled="uploading" />
            </label>
          </CardContent>
        </Card>
        <!-- SQL Upload -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Database class="h-5 w-5" />
              SQL Dump
            </CardTitle>
            <CardDescription>Upload legacy database dump (.sql)</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="!sqlFile" class="space-y-4">
              <label class="flex flex-col items-center justify-center h-32 border-2 border-dashed rounded-lg cursor-pointer hover:bg-muted/50">
                <Upload class="h-8 w-8 text-muted-foreground" />
                <span class="mt-2 text-sm text-muted-foreground">Click to upload SQL file</span>
                <input type="file" accept=".sql" class="hidden" @change="handleSqlUpload" :disabled="uploading" />
              </label>
            </div>
            <div v-else class="space-y-2">
              <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                <div class="flex items-center gap-2">
                  <FileText class="h-4 w-4" />
                  <div>
                    <div class="font-medium">{{ sqlFile.filename }}</div>
                    <div class="text-xs text-muted-foreground">{{ formatSize(sqlFile.size) }}</div>
                  </div>
                </div>
                <Button variant="ghost" size="sm" @click="removeFile('sql')">Remove</Button>
              </div>
              <div v-if="sqlFile.info" class="text-xs text-muted-foreground p-2">
                Tables: {{ sqlFile.info.tables?.join(', ') || 'N/A' }}
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- CSV Upload -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <FileText class="h-5 w-5" />
              CSV Files
            </CardTitle>
            <CardDescription>Upload meter reading CSV files</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <label class="flex flex-col items-center justify-center h-32 border-2 border-dashed rounded-lg cursor-pointer hover:bg-muted/50">
                <Upload class="h-8 w-8 text-muted-foreground" />
                <span class="mt-2 text-sm text-muted-foreground">Click to upload CSV files</span>
                <input type="file" accept=".csv" multiple class="hidden" @change="handleCsvUpload" :disabled="uploading" />
              </label>
              
              <div v-if="csvFiles.length > 0" class="space-y-2">
                <div v-for="(file, index) in csvFiles" :key="index" class="flex items-center justify-between p-2 bg-muted rounded-lg text-sm">
                  <div class="flex items-center gap-2">
                    <FileText class="h-4 w-4" />
                    <span>{{ file.filename }}</span>
                    <span class="text-xs text-muted-foreground">({{ file.info?.row_count }} rows)</span>
                  </div>
                  <Button variant="ghost" size="sm" @click="removeFile('csv', index)">×</Button>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Import Options -->
      <Card>
        <CardHeader>
          <CardTitle>Import Options</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex items-center space-x-2">
            <Checkbox id="create-meters" v-model:checked="options.create_missing_meters" />
            <label for="create-meters" class="text-sm cursor-pointer">
              Auto-create missing meters from CSV data
            </label>
          </div>
          <div class="flex items-center space-x-2">
            <Checkbox id="update-timestamps" v-model:checked="options.update_timestamps" />
            <label for="update-timestamps" class="text-sm cursor-pointer">
              Update meter last_log_update timestamps
            </label>
          </div>
        </CardContent>
      </Card>

      <!-- Import Button -->
      <div class="flex justify-end">
        <Button 
          @click="startImport" 
          :disabled="uploading || importing || (!sqlFile && csvFiles.length === 0)"
          size="lg"
        >
          <Loader2 v-if="importing" class="mr-2 h-4 w-4 animate-spin" />
          {{ importing ? 'Importing...' : 'Start Import' }}
        </Button>
      </div>

      <!-- Progress Section -->
      <div v-if="importJobs.length > 0" class="space-y-4">
        <h2 class="text-xl font-semibold">Import Progress</h2>
        <Card v-for="job in importJobs" :key="job.id">
          <CardContent class="pt-6">
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <component :is="getStatusIcon(job.status)" :class="[getStatusColor(job.status), job.status === 'processing' ? 'animate-spin' : '', 'h-5 w-5']" />
                  <span class="font-medium">{{ job.filename }}</span>
                </div>
                <span :class="getStatusColor(job.status)" class="text-sm capitalize">{{ job.status }}</span>
              </div>
              
              <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="h-full bg-blue-500 transition-all duration-300" :style="{ width: job.progress + '%' }"></div>
              </div>
              
              <div class="flex justify-between text-xs text-muted-foreground">
                <span>{{ job.processed }} / {{ job.total }} records</span>
                <span>{{ job.duration || '0s' }}</span>
              </div>

              <div v-if="job.error" class="text-sm text-red-500 bg-red-50 p-2 rounded">
                {{ job.error }}
              </div>

              <div v-if="job.result && job.status === 'completed'" class="text-sm text-green-600 bg-green-50 p-3 rounded space-y-1">
                <div v-for="(value, key) in job.result" :key="key">
                  <strong>{{ key.replace(/_/g, ' ') }}:</strong> {{ value }}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
