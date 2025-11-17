<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm, Link, Head } from '@inertiajs/vue3'
import * as gateways from '@/actions/App/Http/Controllers/GatewayController'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
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

const submit = () => {
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
              <div class="space-y-2">
                <Label for="site_id">Site *</Label>
                <Select v-model="form.site_id" required>
                  <SelectTrigger>
                    <SelectValue placeholder="Select site" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="site in props.sites" :key="site.id" :value="site.id.toString()">
                      {{ site.code }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.site_id" class="text-sm text-destructive">{{ form.errors.site_id }}</p>
              </div>

              <div class="space-y-2">
                <Label for="location_id">Location</Label>
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
              </div>
            </div>

            <div class="space-y-2">
              <Label for="serial_number">Serial Number *</Label>
              <Input
                id="serial_number"
                v-model="form.serial_number"
                placeholder="e.g., GW-12345"
                required
              />
              <p v-if="form.errors.serial_number" class="text-sm text-destructive">{{ form.errors.serial_number }}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Network Configuration</CardTitle>
            <CardDescription>Gateway network settings</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="mac_address">MAC Address</Label>
                <Input
                  id="mac_address"
                  v-model="form.mac_address"
                  placeholder="00:11:22:33:44:55"
                />
              </div>

              <div class="space-y-2">
                <Label for="ip_address">IP Address</Label>
                <Input
                  id="ip_address"
                  v-model="form.ip_address"
                  placeholder="192.168.1.100"
                />
              </div>

              <div class="space-y-2">
                <Label for="ip_netmask">Netmask</Label>
                <Input
                  id="ip_netmask"
                  v-model="form.ip_netmask"
                  placeholder="255.255.255.0"
                />
              </div>

              <div class="space-y-2">
                <Label for="ip_gateway">Gateway IP</Label>
                <Input
                  id="ip_gateway"
                  v-model="form.ip_gateway"
                  placeholder="192.168.1.1"
                />
              </div>

              <div class="space-y-2">
                <Label for="server_ip">Server IP</Label>
                <Input
                  id="server_ip"
                  v-model="form.server_ip"
                  placeholder="Server IP address"
                />
              </div>

              <div class="space-y-2">
                <Label for="connection_type">Connection Type</Label>
                <Input
                  id="connection_type"
                  v-model="form.connection_type"
                  placeholder="e.g., Ethernet, WiFi"
                />
              </div>
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
