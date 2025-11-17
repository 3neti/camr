<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import * as users from '@/actions/App/Http/Controllers/UserController'
import { ArrowLeft, Users } from 'lucide-vue-next'

interface Props {
  user: {
    id: number
    name: string
    email: string
    job_title: string | null
    role: string
    access_level: string
    expires_at: string | null
    is_active: boolean
    site_ids: number[]
  }
  sites: Array<{ id: number; code: string }>
}

const props = defineProps<Props>()

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  password: '',
  password_confirmation: '',
  job_title: props.user.job_title || '',
  role: props.user.role,
  access_level: props.user.access_level,
  expires_at: props.user.expires_at || '',
  is_active: props.user.is_active,
  site_ids: props.user.site_ids,
})

function submit() {
  form.put(users.update({ user: props.user.id }).url)
}

function toggleSite(siteId: number) {
  const index = form.site_ids.indexOf(siteId)
  if (index > -1) {
    form.site_ids.splice(index, 1)
  } else {
    form.site_ids.push(siteId)
  }
}
</script>

<template>
  <Head :title="`Edit User: ${user.name}`" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center gap-4">
        <Link :href="users.index().url">
          <Button variant="ghost" size="sm">
            <ArrowLeft class="h-4 w-4 mr-2" />
            Back
          </Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
            <Users class="h-8 w-8" />
            Edit User: {{ user.name }}
          </h1>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>User Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="name">Name *</Label>
                <Input id="name" v-model="form.name" required />
                <span v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</span>
              </div>

              <div class="space-y-2">
                <Label for="email">Email *</Label>
                <Input id="email" v-model="form.email" type="email" required />
                <span v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</span>
              </div>

              <div class="space-y-2">
                <Label for="password">New Password (leave blank to keep current)</Label>
                <Input id="password" v-model="form.password" type="password" />
                <span v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</span>
              </div>

              <div class="space-y-2">
                <Label for="password_confirmation">Confirm Password</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
              </div>

              <div class="space-y-2">
                <Label for="job_title">Job Title</Label>
                <Input id="job_title" v-model="form.job_title" />
              </div>

              <div class="space-y-2">
                <Label for="expires_at">Expires At</Label>
                <Input id="expires_at" v-model="form.expires_at" type="date" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Permissions</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="role">Role *</Label>
                <Select v-model="form.role">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="admin">Admin</SelectItem>
                    <SelectItem value="user">User</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="space-y-2">
                <Label for="access_level">Access Level *</Label>
                <Select v-model="form.access_level">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Sites</SelectItem>
                    <SelectItem value="selected">Selected Sites</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <Checkbox id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
              <Label for="is_active">Active</Label>
            </div>

            <div v-if="form.access_level === 'selected'" class="space-y-2">
              <Label>Assigned Sites</Label>
              <div class="grid md:grid-cols-3 gap-2 p-4 border rounded">
                <div v-for="site in props.sites" :key="site.id" class="flex items-center space-x-2">
                  <Checkbox
                    :id="`site-${site.id}`"
                    :checked="form.site_ids.includes(site.id)"
                    @update:checked="toggleSite(site.id)"
                  />
                  <Label :for="`site-${site.id}`">{{ site.code }}</Label>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
          <Link :href="users.index().url">
            <Button type="button" variant="outline">Cancel</Button>
          </Link>
          <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Updating...' : 'Update User' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
