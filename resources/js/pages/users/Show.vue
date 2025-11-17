<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import * as users from '@/actions/App/Http/Controllers/UserController'
import { ArrowLeft, Pencil, Users } from 'lucide-vue-next'

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
    sites: Array<{ id: number; code: string }>
  }
}

const props = defineProps<Props>()

function getStatusBadge() {
  if (!props.user.is_active) return { variant: 'destructive', label: 'Inactive' }
  if (props.user.expires_at && new Date(props.user.expires_at) < new Date()) return { variant: 'secondary', label: 'Expired' }
  return { variant: 'default', label: 'Active' }
}
</script>

<template>
  <Head :title="`User: ${user.name}`" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
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
              {{ user.name }}
              <Badge :variant="getStatusBadge().variant">
                {{ getStatusBadge().label }}
              </Badge>
            </h1>
            <p class="text-muted-foreground">{{ user.email }}</p>
          </div>
        </div>
        <Link :href="users.edit({ user: user.id }).url">
          <Button>
            <Pencil class="h-4 w-4 mr-2" />
            Edit User
          </Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>User Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
          <div class="grid grid-cols-2 gap-4">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Name:</span>
              <span class="font-medium">{{ user.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Email:</span>
              <span class="font-medium">{{ user.email }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Job Title:</span>
              <span class="font-medium">{{ user.job_title || '—' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Role:</span>
              <Badge :variant="user.role === 'admin' ? 'default' : 'secondary'">
                {{ user.role }}
              </Badge>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Access Level:</span>
              <Badge variant="outline">{{ user.access_level }}</Badge>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Expires At:</span>
              <span class="font-medium">{{ user.expires_at ? new Date(user.expires_at).toLocaleDateString() : 'Never' }}</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card v-if="user.access_level === 'selected'">
        <CardHeader>
          <CardTitle>Assigned Sites ({{ user.sites.length }})</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <Badge v-for="site in user.sites" :key="site.id" variant="outline">
              {{ site.code }}
            </Badge>
            <p v-if="user.sites.length === 0" class="text-muted-foreground">No sites assigned</p>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
