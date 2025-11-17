<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import * as users from '@/actions/App/Http/Controllers/UserController'
import { Plus, Users, Search, Eye, Pencil, Trash2 } from 'lucide-vue-next'

interface User {
  id: number
  name: string
  email: string
  job_title: string | null
  role: string
  access_level: string
  is_active: boolean
  expires_at: string | null
  sites: Array<{ id: number; code: string }>
}

interface Props {
  users: {
    data: User[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: Array<{ url: string | null; label: string; active: boolean }>
  }
  filters: {
    search?: string
    role?: string
    status?: string
  }
}

const props = defineProps<Props>()

const search = ref(props.filters.search || '')
const roleFilter = ref(props.filters.role || 'all')
const statusFilter = ref(props.filters.status || 'all')

function applyFilters() {
  router.get(users.index().url, {
    search: search.value || undefined,
    role: roleFilter.value !== 'all' ? roleFilter.value : undefined,
    status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

function deleteUser(user: User) {
  if (confirm(`Are you sure you want to delete ${user.name}?`)) {
    router.delete(users.destroy({ user: user.id }).url)
  }
}

function getStatusBadge(user: User) {
  if (!user.is_active) return { variant: 'destructive', label: 'Inactive' }
  if (user.expires_at && new Date(user.expires_at) < new Date()) return { variant: 'secondary', label: 'Expired' }
  return { variant: 'default', label: 'Active' }
}
</script>

<template>
  <Head title="Users" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
            <Users class="h-8 w-8" />
            Users
          </h1>
          <p class="text-muted-foreground">Manage system users and permissions</p>
        </div>
        <Link :href="users.create().url">
          <Button>
            <Plus class="h-4 w-4 mr-2" />
            Add User
          </Button>
        </Link>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
          <CardDescription>Search and filter users</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Search</label>
              <div class="relative">
                <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  v-model="search"
                  placeholder="Name or email..."
                  class="pl-8"
                  @keyup.enter="applyFilters"
                />
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Role</label>
              <Select v-model="roleFilter" @update:model-value="applyFilters">
                <SelectTrigger>
                  <SelectValue placeholder="All roles" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Roles</SelectItem>
                  <SelectItem value="admin">Admin</SelectItem>
                  <SelectItem value="user">User</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Status</label>
              <Select v-model="statusFilter" @update:model-value="applyFilters">
                <SelectTrigger>
                  <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Statuses</SelectItem>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                  <SelectItem value="expired">Expired</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="flex items-end">
              <Button @click="applyFilters" class="w-full">
                Apply Filters
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Users Table -->
      <Card>
        <CardHeader>
          <CardTitle>Users ({{ props.users.total }})</CardTitle>
          <CardDescription>{{ props.users.current_page }} of {{ props.users.last_page }} pages</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Job Title</TableHead>
                <TableHead>Role</TableHead>
                <TableHead>Access</TableHead>
                <TableHead>Sites</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="props.users.data.length === 0">
                <TableCell colspan="8" class="text-center text-muted-foreground">
                  No users found
                </TableCell>
              </TableRow>
              <TableRow v-for="user in props.users.data" :key="user.id">
                <TableCell class="font-medium">{{ user.name }}</TableCell>
                <TableCell>{{ user.email }}</TableCell>
                <TableCell>{{ user.job_title || '—' }}</TableCell>
                <TableCell>
                  <Badge :variant="user.role === 'admin' ? 'default' : 'secondary'">
                    {{ user.role }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge variant="outline">{{ user.access_level }}</Badge>
                </TableCell>
                <TableCell>
                  <span v-if="user.access_level === 'all'">All Sites</span>
                  <span v-else>{{ user.sites.length }} sites</span>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusBadge(user).variant">
                    {{ getStatusBadge(user).label }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button size="sm" variant="ghost" @click="router.visit(users.show({ user: user.id }).url)">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="ghost" @click="router.visit(users.edit({ user: user.id }).url)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="ghost" @click="deleteUser(user)">
                      <Trash2 class="h-4 w-4 text-red-500" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <!-- Pagination -->
          <div v-if="props.users.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
            <Button
              v-for="link in props.users.links"
              :key="link.label"
              :variant="link.active ? 'default' : 'outline'"
              size="sm"
              :disabled="!link.url"
              @click="link.url && router.visit(link.url)"
              v-html="link.label"
            />
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
