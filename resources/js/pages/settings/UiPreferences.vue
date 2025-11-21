<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'

interface Props {
  uiSettings: {
    show_buildings: boolean
    show_locations: boolean
    show_config_files: boolean
  }
}

const props = defineProps<Props>()

const form = useForm({
  show_buildings: props.uiSettings.show_buildings,
  show_locations: props.uiSettings.show_locations,
  show_config_files: props.uiSettings.show_config_files,
})

const submit = () => {
  form.patch(route('ui-preferences.update'))
}
</script>

<template>
  <Head title="Sidebar Preferences" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6 max-w-2xl">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Sidebar Preferences</h1>
        <p class="text-muted-foreground">Configure which menu items appear in the sidebar</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Navigation Items</CardTitle>
          <CardDescription>Show or hide additional menu items in the sidebar</CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="space-y-4">
            <!-- Buildings Toggle -->
            <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50">
              <div class="flex-1">
                <Label for="buildings" class="text-base font-medium cursor-pointer">Buildings</Label>
                <p class="text-sm text-muted-foreground mt-1">Show Buildings management in the sidebar</p>
              </div>
              <Checkbox
                id="buildings"
                v-model:checked="form.show_buildings"
                class="h-5 w-5"
              />
            </div>

            <!-- Locations Toggle -->
            <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50">
              <div class="flex-1">
                <Label for="locations" class="text-base font-medium cursor-pointer">Locations</Label>
                <p class="text-sm text-muted-foreground mt-1">Show Locations management in the sidebar</p>
              </div>
              <Checkbox
                id="locations"
                v-model:checked="form.show_locations"
                class="h-5 w-5"
              />
            </div>

            <!-- Config Files Toggle -->
            <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50">
              <div class="flex-1">
                <Label for="config-files" class="text-base font-medium cursor-pointer">Config Files</Label>
                <p class="text-sm text-muted-foreground mt-1">Show Configuration Files management in the sidebar</p>
              </div>
              <Checkbox
                id="config-files"
                v-model:checked="form.show_config_files"
                class="h-5 w-5"
              />
            </div>
          </div>

          <!-- Info Alert -->
          <Alert>
            <AlertDescription>
              Changes are applied immediately to your sidebar navigation.
            </AlertDescription>
          </Alert>

          <!-- Actions -->
          <div class="flex gap-3 pt-4">
            <Button @click="submit" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Save Preferences' }}
            </Button>
            <Link :href="route('dashboard')">
              <Button variant="outline">Cancel</Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
