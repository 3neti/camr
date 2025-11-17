<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm, Link, Head } from '@inertiajs/vue3'
import * as meters from '@/actions/App/Http/Controllers/MeterController'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { ArrowLeft, Zap } from 'lucide-vue-next'

interface Meter {
  id: number
  gateway_id: number
  location_id: number | null
  name: string
  type: string
  brand: string
  customer_name: string
  status: string
  is_addressable: boolean
  has_load_profile: boolean
}

interface Props {
  meter: Meter
  gateways: Array<{ id: number; serial_number: string }>
  locations: Array<{ id: number; code: string; description: string }>
}

const props = defineProps<Props>()

const form = useForm({
  gateway_id: props.meter.gateway_id.toString(),
  location_id: props.meter.location_id?.toString() || '',
  name: props.meter.name,
  type: props.meter.type,
  brand: props.meter.brand,
  customer_name: props.meter.customer_name,
  status: props.meter.status,
  is_addressable: props.meter.is_addressable,
  has_load_profile: props.meter.has_load_profile,
})

const submit = () => form.put(meters.update({ meter: props.meter.id }).url)
</script>

<template>
  <Head :title="`Edit Meter: ${meter.name}`" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center gap-4">
        <Link :href="meters.show({ meter: meter.id }).url">
          <Button variant="ghost" size="sm"><ArrowLeft class="h-4 w-4 mr-2" />Back</Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2"><Zap class="h-8 w-8" />Edit Meter</h1>
          <p class="text-muted-foreground">{{ meter.name }}</p>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader><CardTitle>Basic Information</CardTitle></CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label>Gateway *</Label>
                <Select v-model="form.gateway_id">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="gw in props.gateways" :key="gw.id" :value="gw.id.toString()">{{ gw.serial_number }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-2">
                <Label>Location</Label>
                <Select v-model="form.location_id">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">None</SelectItem>
                    <SelectItem v-for="loc in props.locations" :key="loc.id" :value="loc.id.toString()">{{ loc.code }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="space-y-2">
              <Label>Meter Name *</Label>
              <Input v-model="form.name" required />
              <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label>Type *</Label>
                <Input v-model="form.type" required />
              </div>
              <div class="space-y-2">
                <Label>Brand *</Label>
                <Input v-model="form.brand" required />
              </div>
              <div class="space-y-2">
                <Label>Status *</Label>
                <Select v-model="form.status">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Active">Active</SelectItem>
                    <SelectItem value="Inactive">Inactive</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="space-y-2">
              <Label>Customer Name *</Label>
              <Input v-model="form.customer_name" required />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="flex items-center space-x-2">
                <Checkbox v-model:checked="form.is_addressable" />
                <Label>Is Addressable</Label>
              </div>
              <div class="flex items-center space-x-2">
                <Checkbox v-model:checked="form.has_load_profile" />
                <Label>Has Load Profile</Label>
              </div>
            </div>
          </CardContent>
        </Card>

        <div class="flex justify-end gap-4">
          <Link :href="meters.show({ meter: meter.id }).url">
            <Button type="button" variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :disabled="form.processing">Update Meter</Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
