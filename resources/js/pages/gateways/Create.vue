<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm, Link, Head } from '@inertiajs/vue3'
import * as gateways from '@/actions/App/Http/Controllers/GatewayController'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import FormField from '@/components/FormField.vue'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { ArrowLeft, Radio } from 'lucide-vue-next'
import { useValidation, validationRules } from '@/composables/useValidation'
import { ref, watch, onMounted } from 'vue'

interface Site {
  id: number
  code: string
}

interface Location {
  id: number
  code: string
  description: string
}

interface Props {
  sites: Site[]
  locations: Location[]
}

const props = defineProps<Props>()

const form = useForm({
  site_id: '',
  location_id: '',
  serial_number: '',
  mac_address: '',
  ip_address: '',
  connection_type: '',
  ip_netmask: '',
  ip_gateway: '',
  server_ip: '',
  site_code: '',
  description: '',
  update_csv: false,
  update_site_code: false,
  ssh_enabled: false,
  force_load_profile: false,
  idf_number: '',
  switch_name: '',
  idf_port: '',
  software_version: '',
})

// Setup validation
const validation = useValidation()

onMounted(() => {
  // Register fields with validation rules
  validation.registerField('site_id', [validationRules.required('Please select a site')])
  validation.registerField('serial_number', [
    validationRules.required('Serial number is required'),
    validationRules.minLength(3, 'Serial number must be at least 3 characters'),
  ])
  validation.registerField('mac_address', [
    validationRules.macAddress('Invalid MAC address format (e.g., 00:11:22:33:44:55)'),
  ])
  validation.registerField('ip_address', [
    validationRules.ipAddress('Invalid IP address format'),
  ])
  validation.registerField('ip_netmask', [
    validationRules.ipAddress('Invalid netmask format'),
  ])
  validation.registerField('ip_gateway', [
    validationRules.ipAddress('Invalid gateway IP format'),
  ])
  validation.registerField('server_ip', [
    validationRules.ipAddress('Invalid server IP format'),
  ])
})

// Watch form fields and validate on change
watch(() => form.site_id, (value) => {
  validation.markDirty('site_id')
  validation.validateField('site_id', value)
})

watch(() => form.serial_number, (value) => {
  validation.markDirty('serial_number')
  validation.validateField('serial_number', value)
})

watch(() => form.mac_address, (value) => {
  if (value) {
    validation.markDirty('mac_address')
    validation.validateField('mac_address', value)
  }
})

watch(() => form.ip_address, (value) => {
  if (value) {
    validation.markDirty('ip_address')
    validation.validateField('ip_address', value)
  }
})

watch(() => form.ip_netmask, (value) => {
  if (value) {
    validation.markDirty('ip_netmask')
    validation.validateField('ip_netmask', value)
  }
})

watch(() => form.ip_gateway, (value) => {
  if (value) {
    validation.markDirty('ip_gateway')
    validation.validateField('ip_gateway', value)
  }
})

watch(() => form.server_ip, (value) => {
  if (value) {
    validation.markDirty('server_ip')
    validation.validateField('server_ip', value)
  }
})

const submit = () => {
  // Validate all fields before submission
  const isValid = validation.validateAll({
    site_id: form.site_id,
    serial_number: form.serial_number,
    mac_address: form.mac_address,
    ip_address: form.ip_address,
    ip_netmask: form.ip_netmask,
    ip_gateway: form.ip_gateway,
    server_ip: form.server_ip,
  })

  if (!isValid) {
    // Mark all fields as touched to show errors
    Object.keys(validation.fields.value).forEach(field => {
      validation.touchField(field)
    })
    return
  }

  form.post(gateways.store().url)
}
</script>

<template>
  <Head title="Create Gateway" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center gap-4">
        <Link :href="gateways.index().url">
          <Button variant="ghost" size="sm">
            <ArrowLeft class="h-4 w-4 mr-2" />
            Back
          </Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
            <Radio class="h-8 w-8" />
            Create Gateway
          </h1>
          <p class="text-muted-foreground">Add a new gateway to the system</p>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Basic Information</CardTitle>
            <CardDescription>Required gateway identification</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <FormField 
                label="Site" 
                name="site_id" 
                :error="validation.shouldShowError('site_id') ? validation.getError('site_id') : (form.errors.site_id || undefined)"
                required
              >
                <Select v-model="form.site_id" @update:model-value="() => validation.touchField('site_id')">
                  <SelectTrigger>
                    <SelectValue placeholder="Select site" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="site in props.sites" :key="site.id" :value="site.id.toString()">
                      {{ site.code }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </FormField>

              <FormField 
                label="Location" 
                name="location_id"
                hint="Optional - specify gateway location"
              >
                <Select v-model="form.location_id">
                  <SelectTrigger>
                    <SelectValue placeholder="Select location (optional)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">None</SelectItem>
                    <SelectItem v-for="location in props.locations" :key="location.id" :value="location.id.toString()">
                      {{ location.code }} - {{ location.description }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </FormField>
            </div>

            <FormField 
              label="Serial Number" 
              name="serial_number" 
              :error="validation.shouldShowError('serial_number') ? validation.getError('serial_number') : (form.errors.serial_number || undefined)"
              hint="Unique identifier for the gateway"
              required
            >
              <Input
                id="serial_number"
                v-model="form.serial_number"
                placeholder="e.g., GW-12345"
                @blur="() => validation.touchField('serial_number')"
              />
            </FormField>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Network Configuration</CardTitle>
            <CardDescription>Gateway network settings</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <FormField 
                label="MAC Address" 
                name="mac_address"
                :error="validation.shouldShowError('mac_address') ? validation.getError('mac_address') : undefined"
                hint="Format: 00:11:22:33:44:55"
              >
                <Input
                  id="mac_address"
                  v-model="form.mac_address"
                  placeholder="00:11:22:33:44:55"
                  @blur="() => validation.touchField('mac_address')"
                />
              </FormField>

              <FormField 
                label="IP Address" 
                name="ip_address"
                :error="validation.shouldShowError('ip_address') ? validation.getError('ip_address') : undefined"
                hint="Gateway's IP address"
              >
                <Input
                  id="ip_address"
                  v-model="form.ip_address"
                  placeholder="192.168.1.100"
                  @blur="() => validation.touchField('ip_address')"
                />
              </FormField>

              <FormField 
                label="Netmask" 
                name="ip_netmask"
                :error="validation.shouldShowError('ip_netmask') ? validation.getError('ip_netmask') : undefined"
                hint="Network subnet mask"
              >
                <Input
                  id="ip_netmask"
                  v-model="form.ip_netmask"
                  placeholder="255.255.255.0"
                  @blur="() => validation.touchField('ip_netmask')"
                />
              </FormField>

              <FormField 
                label="Gateway IP" 
                name="ip_gateway"
                :error="validation.shouldShowError('ip_gateway') ? validation.getError('ip_gateway') : undefined"
                hint="Network gateway address"
              >
                <Input
                  id="ip_gateway"
                  v-model="form.ip_gateway"
                  placeholder="192.168.1.1"
                  @blur="() => validation.touchField('ip_gateway')"
                />
              </FormField>

              <FormField 
                label="Server IP" 
                name="server_ip"
                :error="validation.shouldShowError('server_ip') ? validation.getError('server_ip') : undefined"
                hint="CAMR server IP address"
              >
                <Input
                  id="server_ip"
                  v-model="form.server_ip"
                  placeholder="Server IP address"
                  @blur="() => validation.touchField('server_ip')"
                />
              </FormField>

              <FormField 
                label="Connection Type" 
                name="connection_type"
                hint="e.g., Ethernet, WiFi, Cellular"
              >
                <Input
                  id="connection_type"
                  v-model="form.connection_type"
                  placeholder="e.g., Ethernet, WiFi"
                />
              </FormField>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Configuration Flags</CardTitle>
            <CardDescription>Gateway behavior settings</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="flex items-center space-x-2">
                <Checkbox id="update_csv" v-model:checked="form.update_csv" />
                <Label for="update_csv">Update CSV</Label>
              </div>

              <div class="flex items-center space-x-2">
                <Checkbox id="update_site_code" v-model:checked="form.update_site_code" />
                <Label for="update_site_code">Update Site Code</Label>
              </div>

              <div class="flex items-center space-x-2">
                <Checkbox id="ssh_enabled" v-model:checked="form.ssh_enabled" />
                <Label for="ssh_enabled">SSH Enabled</Label>
              </div>

              <div class="flex items-center space-x-2">
                <Checkbox id="force_load_profile" v-model:checked="form.force_load_profile" />
                <Label for="force_load_profile">Force Load Profile</Label>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Additional Details</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="idf_number">IDF Number</Label>
                <Input
                  id="idf_number"
                  v-model="form.idf_number"
                  placeholder="IDF-001"
                />
              </div>

              <div class="space-y-2">
                <Label for="switch_name">Switch Name</Label>
                <Input
                  id="switch_name"
                  v-model="form.switch_name"
                  placeholder="Switch name"
                />
              </div>

              <div class="space-y-2">
                <Label for="idf_port">IDF Port</Label>
                <Input
                  id="idf_port"
                  v-model="form.idf_port"
                  placeholder="Port number"
                />
              </div>
            </div>

            <div class="space-y-2">
              <Label for="software_version">Software Version</Label>
              <Input
                id="software_version"
                v-model="form.software_version"
                placeholder="v1.0.0"
              />
            </div>

            <div class="space-y-2">
              <Label for="description">Description</Label>
              <Input
                id="description"
                v-model="form.description"
                placeholder="Optional description"
              />
            </div>
          </CardContent>
        </Card>

        <div class="flex justify-end gap-4">
          <Link :href="gateways.index().url">
            <Button type="button" variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :disabled="form.processing">
            Create Gateway
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
