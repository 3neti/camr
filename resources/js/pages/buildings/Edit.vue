<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import * as buildings from '@/actions/App/Http/Controllers/BuildingController'
import { ArrowLeft, Building2 } from 'lucide-vue-next'

interface Props {
  building: { id: number; site_id: number; code: string; description: string | null }
  sites: Array<{ id: number; code: string }>
}

const props = defineProps<Props>()

const form = useForm({
  site_id: props.building.site_id.toString(),
  code: props.building.code,
  description: props.building.description || '',
})

function submit() {
  form.put(buildings.update({ building: props.building.id }).url)
}
</script>

<template>
  <Head :title="`Edit Building: ${building.code}`" />
  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center gap-4">
        <Link :href="buildings.index().url">
          <Button variant="ghost" size="sm"><ArrowLeft class="h-4 w-4 mr-2" />Back</Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
            <Building2 class="h-8 w-8" />
            Edit Building: {{ building.code }}
          </h1>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader><CardTitle>Building Information</CardTitle></CardHeader>
          <CardContent class="space-y-4">
            <div class="grid md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="site_id">Site *</Label>
                <Select v-model="form.site_id">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="site in props.sites" :key="site.id" :value="site.id.toString()">{{ site.code }}</SelectItem>
                  </SelectContent>
                </Select>
                <span v-if="form.errors.site_id" class="text-sm text-red-500">{{ form.errors.site_id }}</span>
              </div>

              <div class="space-y-2">
                <Label for="code">Code *</Label>
                <Input id="code" v-model="form.code" required />
                <span v-if="form.errors.code" class="text-sm text-red-500">{{ form.errors.code }}</span>
              </div>

              <div class="md:col-span-2 space-y-2">
                <Label for="description">Description</Label>
                <Textarea id="description" v-model="form.description" rows="3" />
              </div>
            </div>
          </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
          <Link :href="buildings.index().url"><Button type="button" variant="outline">Cancel</Button></Link>
          <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Updating...' : 'Update Building' }}</Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
