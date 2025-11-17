<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm, Link, Head } from '@inertiajs/vue3'
import * as sites from '@/actions/App/Http/Controllers/SiteController'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
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
import { ArrowLeft, Save } from 'lucide-vue-next'

interface Company {
  id: number
  code: string
  name: string
}

interface Division {
  id: number
  code: string
  name: string
}

interface Props {
  companies: Company[]
  divisions: Division[]
}

const props = defineProps<Props>()

const form = useForm({
  company_id: null as number | null,
  division_id: null as number | null,
  code: '',
})

const submit = () => {
  form.post(sites.store().url)
}
</script>

<template>
  <Head title="Create Site" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6 max-w-2xl">
      <div class="flex items-center gap-4">
        <Link :href="sites.index().url">
          <Button variant="ghost" size="sm">
            <ArrowLeft class="h-4 w-4 mr-2" />
            Back to Sites
          </Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Create New Site</CardTitle>
          <CardDescription>
            Add a new CAMR site for monitoring electricity consumption
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Company -->
            <div class="space-y-2">
              <Label for="company_id">
                Company
                <span class="text-destructive">*</span>
              </Label>
              <Select v-model="form.company_id" required>
                <SelectTrigger>
                  <SelectValue placeholder="Select a company" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="company in companies"
                    :key="company.id"
                    :value="company.id"
                  >
                    {{ company.name }} ({{ company.code }})
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.company_id" class="text-sm text-destructive">
                {{ form.errors.company_id }}
              </p>
            </div>

            <!-- Division -->
            <div class="space-y-2">
              <Label for="division_id">
                Division
                <span class="text-destructive">*</span>
              </Label>
              <Select v-model="form.division_id" required>
                <SelectTrigger>
                  <SelectValue placeholder="Select a division" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="division in divisions"
                    :key="division.id"
                    :value="division.id"
                  >
                    {{ division.name }} ({{ division.code }})
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.division_id" class="text-sm text-destructive">
                {{ form.errors.division_id }}
              </p>
            </div>

            <!-- Site Code -->
            <div class="space-y-2">
              <Label for="code">
                Site Code
                <span class="text-destructive">*</span>
              </Label>
              <Input
                id="code"
                v-model="form.code"
                placeholder="e.g., RG-01"
                required
              />
              <p class="text-sm text-muted-foreground">
                Unique identifier for this site (e.g., building code)
              </p>
              <p v-if="form.errors.code" class="text-sm text-destructive">
                {{ form.errors.code }}
              </p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
              <Link :href="sites.index().url">
                <Button type="button" variant="outline">
                  Cancel
                </Button>
              </Link>
              <Button type="submit" :disabled="form.processing">
                <Save class="h-4 w-4 mr-2" />
                {{ form.processing ? 'Creating...' : 'Create Site' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
