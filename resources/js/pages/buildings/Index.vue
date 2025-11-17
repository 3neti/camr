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
import * as buildings from '@/actions/App/Http/Controllers/BuildingController'
import { Plus, Search, Building2, Pencil, Trash2, Eye } from 'lucide-vue-next'

interface Props {
  buildings: {
    data: Array<{
      id: number
      code: string
      description: string | null
      site: { id: number; code: string }
      created_at: string
    }>
    total: number
    last_page: number
    links: Array<{ url: string | null; label: string; active: boolean }>
  }
  sites: Array<{ id: number; code: string }>
  filters: { search?: string; site_id?: string }
}

const props = defineProps<Props>()
const search = ref(props.filters.search || '')
const siteId = ref(props.filters.site_id || 'all')

function applyFilters() {
  router.get(buildings.index().url, {
    search: search.value || undefined,
    site_id: siteId.value !== 'all' ? siteId.value : undefined,
  }, { preserveState: true, preserveScroll: true })
}
</script>

<template>
  <Head title="Buildings" />
  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
            <Building2 class="h-8 w-8" />
            Buildings
          </h1>
          <p class="text-muted-foreground">Manage site buildings and facilities</p>
        </div>
        <Link :href="buildings.create().url">
          <Button><Plus class="mr-2 h-4 w-4" />Add Building</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>All Buildings</CardTitle>
              <CardDescription>{{ props.buildings.total }} total buildings</CardDescription>
            </div>
            <div class="flex gap-3">
              <Select v-model="siteId" @update:model-value="applyFilters">
                <SelectTrigger class="w-40"><SelectValue placeholder="Site" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Sites</SelectItem>
                  <SelectItem v-for="site in props.sites" :key="site.id" :value="site.id.toString()">{{ site.code }}</SelectItem>
                </SelectContent>
              </Select>
              <div class="relative w-80">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search buildings..." class="pl-10" @keyup.enter="applyFilters" />
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Code</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Site</TableHead>
                <TableHead>Created</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="props.buildings.data.length === 0">
                <TableCell colspan="5" class="text-center text-muted-foreground">No buildings found</TableCell>
              </TableRow>
              <TableRow v-for="building in props.buildings.data" :key="building.id">
                <TableCell class="font-medium">{{ building.code }}</TableCell>
                <TableCell>{{ building.description || '—' }}</TableCell>
                <TableCell><Badge variant="outline">{{ building.site.code }}</Badge></TableCell>
                <TableCell>{{ new Date(building.created_at).toLocaleDateString() }}</TableCell>
                <TableCell class="text-right">
                  <div class="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" @click="router.visit(buildings.show({ building: building.id }).url)">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="router.visit(buildings.edit({ building: building.id }).url)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="router.delete(buildings.destroy({ building: building.id }).url)">
                      <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <div v-if="props.buildings.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
            <Button v-for="link in props.buildings.links" :key="link.label" :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" @click="link.url && router.visit(link.url)" v-html="link.label" />
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
