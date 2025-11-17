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

interface Props {
  configFile: { 
    id: number
    meter_model: string
    config_file_content: string
  }
}

const props = defineProps<Props>()

const form = useForm({
  meter_model: props.configFile.meter_model,
  config_file_content: props.configFile.config_file_content,
})

function submit() {
  form.put(configFiles.update({ configFile: props.configFile.id }).url)
}
</script>

<template>
  <Head :title="`Edit Config: ${configFile.meter_model}`" />
  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center gap-4">
        <Link :href="configFiles.index().url">
          <Button variant="ghost" size="sm"><ArrowLeft class="h-4 w-4 mr-2" />Back</Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
            <FileCode class="h-8 w-8" />
            Edit Config: {{ configFile.meter_model }}
          </h1>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Configuration Information</CardTitle>
            <CardDescription>Update meter configuration file</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="meter_model">Meter Model *</Label>
              <Input id="meter_model" v-model="form.meter_model" required />
              <span v-if="form.errors.meter_model" class="text-sm text-red-500">{{ form.errors.meter_model }}</span>
            </div>

            <div class="space-y-2">
              <Label for="config_file_content">Configuration Content *</Label>
              <Textarea id="config_file_content" v-model="form.config_file_content" rows="15" required class="font-mono text-sm" />
              <span v-if="form.errors.config_file_content" class="text-sm text-red-500">{{ form.errors.config_file_content }}</span>
            </div>
          </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
          <Link :href="configFiles.index().url"><Button type="button" variant="outline">Cancel</Button></Link>
          <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Updating...' : 'Update Config File' }}</Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
