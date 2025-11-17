<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import * as locations from '@/actions/App/Http/Controllers/LocationController'
import { ArrowLeft, MapPin } from 'lucide-vue-next'

interface Props {
  sites: Array<{ id: number; code: string }>
  buildings: Array<{ id: number; code: string; site_id: number; description: string }>
}

const props = defineProps<Props>()

const form = useForm({
  site_id: '',
  building_id: '',
  code: '',
  description: '',
})

const filteredBuildings = computed(() => {
  if (!form.site_id) return []
  return props.buildings.filter(b => b.site_id === parseInt(form.site_id))
})

function submit() {
  form.post(locations.store().url)
}
</script>

<template>
  <Head title="Create Location" />
  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center gap-4">
        <Link :href="locations.index().url">
          <Button variant="ghost" size="sm"><ArrowLeft class="h-4 w-4 mr-2" />Back</Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
            <MapPin class="h-8 w-8" />
            Create Location
          </h1>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Location Information</CardTitle>
            <CardDescription>Add a new facility location</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="site_id">Site *</Label>
              <Select v-model="form.site_id" required>
                <SelectTrigger><SelectValue placeholder="Select a site" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="site in props.sites" :key="site.id" :value="site.id.toString()">{{ site.code }}</SelectItem>
                </SelectContent>
              </Select>
              <span v-if="form.errors.site_id" class="text-sm text-red-500">{{ form.errors.site_id }}</span>
            </div>

            <div class="space-y-2">
              <Label for="building_id">Building (Optional)</Label>
              <Select v-model="form.building_id" :disabled="!form.site_id">
                <SelectTrigger><SelectValue placeholder="Select a building" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="">None</SelectItem>
                  <SelectItem v-for="building in filteredBuildings" :key="building.id" :value="building.id.toString()">
                    {{ building.code }} - {{ building.description }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <span v-if="form.errors.building_id" class="text-sm text-red-500">{{ form.errors.building_id }}</span>
            </div>

            <div class="space-y-2">
              <Label for="code">Code *</Label>
              <Input id="code" v-model="form.code" placeholder="e.g., LOC-001" required />
              <span v-if="form.errors.code" class="text-sm text-red-500">{{ form.errors.code }}</span>
            </div>

            <div class="space-y-2">
              <Label for="description">Description *</Label>
              <Textarea id="description" v-model="form.description" placeholder="Location description" rows="4" required />
              <span v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</span>
            </div>
          </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
          <Link :href="locations.index().url"><Button type="button" variant="outline">Cancel</Button></Link>
          <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Creating...' : 'Create Location' }}</Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
