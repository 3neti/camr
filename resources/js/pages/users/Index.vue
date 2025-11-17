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
import { Checkbox } from '@/components/ui/checkbox'
import SortableTableHead from '@/components/SortableTableHead.vue'
import * as users from '@/actions/App/Http/Controllers/UserController'
import { Plus, Users, Search, Eye, Pencil, Trash2, Download } from 'lucide-vue-next'
import { useBulkActions } from '@/composables/useBulkActions'
import { useSortable } from '@/composables/useSortable'
import { useExport } from '@/composables/useExport'
import FilterPresets from '@/components/FilterPresets.vue'
import ColumnPreferences from '@/components/ColumnPreferences.vue'
import { useColumnPreferences } from '@/composables/useColumnPreferences'
import { getTableConfig } from '@/config/tableColumnsLoader'

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
    sort?: string
    direction?: 'asc' | 'desc'
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

// Bulk actions
const bulk = useBulkActions(props.users.data)

function bulkDeleteUsers() {
  if (bulk.selectedIds.value.length === 0) return
  
  if (confirm(`Are you sure you want to delete ${bulk.selectedIds.value.length} users?`)) {
    router.post('/users/bulk-delete', { ids: bulk.selectedIds.value }, {
      onSuccess: () => bulk.clearSelection()
    })
  }
}

// Sorting
const sorting = useSortable(users.index().url, {
  column: props.filters.sort || null,
  direction: props.filters.direction || 'asc',
})

function handleSort(column: string) {
  sorting.sort(column, {
    search: search.value || undefined,
    role: roleFilter.value !== 'all' ? roleFilter.value : undefined,
    status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
  })
}

// Column preferences
const tableConfig = getTableConfig('users')!
const columnPrefs = useColumnPreferences({
  storageKey: tableConfig.storageKey,
  defaultColumns: tableConfig.columns,
})

// Export
const { exportToCSV } = useExport()

function exportUsers() {
  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'job_title', label: 'Job Title' },
    { key: 'role', label: 'Role' },
    { key: 'access_level', label: 'Access Level' },
    { key: 'is_active', label: 'Active' },
    { key: 'expires_at', label: 'Expires At' },
  ]
  
  const timestamp = new Date().toISOString().split('T')[0]
  exportToCSV(props.users.data, `users-${timestamp}`, columns)
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
          <CardDescription>
            {{ props.users.current_page }} of {{ props.users.last_page }} pages
            <span v-if="bulk.hasSelection.value" class="ml-2">
              · <strong>{{ bulk.selectedIds.value.length }}</strong> selected
            </span>
          </CardDescription>
          <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <template v-if="bulk.hasSelection.value">
                <Button variant="destructive" size="sm" @click="bulkDeleteUsers">
                  <Trash2 class="h-4 w-4 mr-2" />
                  Delete Selected ({{ bulk.selectedIds.value.length }})
                </Button>
                <Button variant="outline" size="sm" @click="bulk.clearSelection()">
                  Clear Selection
                </Button>
              </template>
              <Button variant="outline" size="sm" @click="exportUsers">
                <Download class="h-4 w-4 mr-2" />
                Export CSV
              </Button>
            </div>
            <div class="flex items-center gap-2">
              <FilterPresets
                storage-key="users-filter-presets"
                :route-url="users.index().url"
                :current-filters="props.filters"
              />
              <ColumnPreferences
                storage-key="users-column-preferences"
                :default-columns="[
                  { key: 'name', label: 'Name', locked: true },
                  { key: 'email', label: 'Email', locked: true },
                  { key: 'job_title', label: 'Job Title' },
                  { key: 'role', label: 'Role' },
                  { key: 'access_level', label: 'Access Level' },
                  { key: 'sites', label: 'Sites' },
                  { key: 'status', label: 'Status' },
                  { key: 'actions', label: 'Actions', locked: true },
                ]"
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="w-12">
                  <Checkbox 
                    :checked="bulk.allSelected.value" 
                    :indeterminate="bulk.someSelected.value"
                    @update:checked="bulk.allSelected.value = $event" 
                  />
                </TableHead>
                <SortableTableHead column="name" :sort-column="sorting.sortColumn.value" :sort-direction="sorting.sortDirection.value" @sort="handleSort">Name</SortableTableHead>
                <SortableTableHead column="email" :sort-column="sorting.sortColumn.value" :sort-direction="sorting.sortDirection.value" @sort="handleSort">Email</SortableTableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('job_title')">Job Title</TableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('role')">Role</TableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('access_level')">Access</TableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('sites')">Sites</TableHead>
                <TableHead v-if="columnPrefs.isColumnVisible('status')">Status</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="props.users.data.length === 0">
                <TableCell colspan="9" class="text-center text-muted-foreground">
                  No users found
                </TableCell>
              </TableRow>
              <TableRow v-for="user in props.users.data" :key="user.id">
                <TableCell>
                  <Checkbox 
                    :checked="bulk.isSelected(user.id)" 
                    @update:checked="bulk.toggleSelection(user.id)" 
                  />
                </TableCell>
                <TableCell class="font-medium">{{ user.name }}</TableCell>
                <TableCell>{{ user.email }}</TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('job_title')">{{ user.job_title || '—' }}</TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('role')">
                  <Badge :variant="user.role === 'admin' ? 'default' : 'secondary'">
                    {{ user.role }}
                  </Badge>
                </TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('access_level')">
                  <Badge variant="outline">{{ user.access_level }}</Badge>
                </TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('sites')">
                  <span v-if="user.access_level === 'all'">All Sites</span>
                  <span v-else>{{ user.sites.length }} sites</span>
                </TableCell>
                <TableCell v-if="columnPrefs.isColumnVisible('status')">
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
