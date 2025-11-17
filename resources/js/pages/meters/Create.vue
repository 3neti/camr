<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm, Link, Head } from '@inertiajs/vue3'
import * as meters from '@/actions/App/Http/Controllers/MeterController'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { ArrowLeft, Zap } from 'lucide-vue-next'

interface Props {
  gateways: Array<{ id: number; serial_number: string }>
  locations: Array<{ id: number; code: string; description: string }>
  buildings: Array<{ id: number; code: string; description: string }>
}

const props = defineProps<Props>()

const form = useForm({
  gateway_id: '',
  location_id: '',
  building_id: '',
  name: '',
  type: '',
  brand: '',
  customer_name: '',
  role: '',
  remarks: '',
  multiplier: '',
  status: 'Active',
  is_addressable: false,
  has_load_profile: false,
  default_name: '',
  software_version: '',
})

const submit = () => form.post(meters.store().url)
</script>

<template>
  <Head title="Create Meter" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center gap-4">
        <Link :href="meters.index().url">
          <Button variant="ghost" size="sm"><ArrowLeft class="h-4 w-4 mr-2" />Back</Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2"><Zap class="h-8 w-8" />Create Meter</h1>
          <p class="text-muted-foreground">Add a new meter to the system</p>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader><CardTitle>Basic Information</CardTitle></CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="gateway_id">Gateway *</Label>
                <Select v-model="form.gateway_id" required>
                  <SelectTrigger><SelectValue placeholder="Select gateway" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="gw in props.gateways" :key="gw.id" :value="gw.id.toString()">{{ gw.serial_number }}</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.gateway_id" class="text-sm text-destructive">{{ form.errors.gateway_id }}</p>
              </div>

              <div class="space-y-2">
                <Label for="location_id">Location</Label>
                <Select v-model="form.location_id">
                  <SelectTrigger><SelectValue placeholder="Select location (optional)" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">None</SelectItem>
                    <SelectItem v-for="loc in props.locations" :key="loc.id" :value="loc.id.toString()">{{ loc.code }} - {{ loc.description }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="name">Meter Name *</Label>
              <Input id="name" v-model="form.name" placeholder="e.g., METER-001" required />
              <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="type">Type *</Label>
                <Input id="type" v-model="form.type" placeholder="e.g., Electric" required />
              </div>
              <div class="space-y-2">
                <Label for="brand">Brand *</Label>
                <Input id="brand" v-model="form.brand" placeholder="e.g., Schneider" required />
              </div>
              <div class="space-y-2">
                <Label for="status">Status *</Label>
                <Select v-model="form.status" required>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Active">Active</SelectItem>
                    <SelectItem value="Inactive">Inactive</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="space-y-2">
              <Label for="customer_name">Customer Name *</Label>
              <Input id="customer_name" v-model="form.customer_name" placeholder="Customer name" required />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="flex items-center space-x-2">
                <Checkbox id="is_addressable" v-model:checked="form.is_addressable" />
                <Label for="is_addressable">Is Addressable</Label>
              </div>
              <div class="flex items-center space-x-2">
                <Checkbox id="has_load_profile" v-model:checked="form.has_load_profile" />
                <Label for="has_load_profile">Has Load Profile</Label>
              </div>
            </div>
          </CardContent>
        </Card>

        <div class="flex justify-end gap-4">
          <Link :href="meters.index().url">
            <Button type="button" variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :disabled="form.processing">Create Meter</Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
