<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import * as configFiles from '@/actions/App/Http/Controllers/ConfigurationFileController'
import { ArrowLeft, FileCode } from 'lucide-vue-next'

const form = useForm({
  meter_model: '',
  config_file_content: '',
})

function submit() {
  form.post(configFiles.store().url)
}
</script>

<template>
  <Head title="Create Configuration File" />
  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center gap-4">
        <Link :href="configFiles.index().url">
          <Button variant="ghost" size="sm"><ArrowLeft class="h-4 w-4 mr-2" />Back</Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
            <FileCode class="h-8 w-8" />
            Create Configuration File
          </h1>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Configuration Information</CardTitle>
            <CardDescription>Add a new meter configuration file</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="meter_model">Meter Model *</Label>
              <Input id="meter_model" v-model="form.meter_model" placeholder="e.g., GE I-210+, Itron Centron" required />
              <span v-if="form.errors.meter_model" class="text-sm text-red-500">{{ form.errors.meter_model }}</span>
            </div>

            <div class="space-y-2">
              <Label for="config_file_content">Configuration Content *</Label>
              <Textarea id="config_file_content" v-model="form.config_file_content" rows="15" placeholder="Paste meter configuration content here..." required class="font-mono text-sm" />
              <span v-if="form.errors.config_file_content" class="text-sm text-red-500">{{ form.errors.config_file_content }}</span>
              <p class="text-xs text-muted-foreground">Enter the complete configuration file content for this meter model</p>
            </div>
          </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
          <Link :href="configFiles.index().url"><Button type="button" variant="outline">Cancel</Button></Link>
          <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Creating...' : 'Create Config File' }}</Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
