<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
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

interface Site {
  id: number
  code: string
  company_id: number
  division_id: number
}

interface Props {
  site: Site
  companies: Company[]
  divisions: Division[]
}

const props = defineProps<Props>()

const form = useForm({
  company_id: props.site.company_id,
  division_id: props.site.division_id,
  code: props.site.code,
})

const submit = () => {
  form.put(sites.update({ site: props.site.id }).url)
}
</script>

<template>
  <Head title="Edit Site" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6 max-w-2xl">
      <div class="flex items-center gap-4">
        <Link :href="sites.show({ site: site.id }).url">
          <Button variant="ghost" size="sm">
            <ArrowLeft class="h-4 w-4 mr-2" />
            Back to Site
          </Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Edit Site: {{ site.code }}</CardTitle>
          <CardDescription>
            Update site information
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
              <p v-if="form.errors.code" class="text-sm text-destructive">
                {{ form.errors.code }}
              </p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
              <Link :href="sites.show({ site: site.id }).url">
                <Button type="button" variant="outline">
                  Cancel
                </Button>
              </Link>
              <Button type="submit" :disabled="form.processing">
                <Save class="h-4 w-4 mr-2" />
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
