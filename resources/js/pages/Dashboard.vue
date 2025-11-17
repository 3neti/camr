<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Link, Head } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import SimpleLineChart from '@/components/SimpleLineChart.vue'
import * as sites from '@/actions/App/Http/Controllers/SiteController'
import * as gateways from '@/actions/App/Http/Controllers/GatewayController'
import * as meters from '@/actions/App/Http/Controllers/MeterController'
import * as buildings from '@/actions/App/Http/Controllers/BuildingController'
import * as locations from '@/actions/App/Http/Controllers/LocationController'
import * as configFiles from '@/actions/App/Http/Controllers/ConfigurationFileController'
import * as users from '@/actions/App/Http/Controllers/UserController'
import { 
  Building2, 
  Radio, 
  Zap, 
  MapPin, 
  FileCode, 
  Users, 
  Plus,
  Activity,
  TrendingUp,
  AlertCircle,
  Clock,
  BarChart3
} from 'lucide-vue-next'
import { computed } from 'vue'

interface Stats {
  sites: { total: number; online: number; offline: number }
  gateways: { total: number; online: number; offline: number }
  meters: { total: number; active: number; inactive: number }
  buildings: number
  locations: number
  config_files: number
  users: number
}

interface RecentActivity {
  type: string
  action: string
  description: string
  timestamp: string
  url: string
}

interface TrendData {
  date: string
  power: number
  meters: number
}

interface TopMeter {
  name: string
  consumption: number
}

interface Props {
  stats: Stats
  recentActivity: RecentActivity[]
  consumptionTrend: TrendData[]
  topMeters: TopMeter[]
}

const props = defineProps<Props>()

const chartData = computed(() => {
  return props.consumptionTrend.map(item => ({
    label: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
    value: item.power,
  }))
})

function getActivityIcon(type: string) {
  switch (type) {
    case 'site': return Building2
    case 'gateway': return Radio
    case 'meter': return Zap
    default: return Activity
  }
}

function getActivityColor(type: string) {
  switch (type) {
    case 'site': return 'text-blue-500'
    case 'gateway': return 'text-purple-500'
    case 'meter': return 'text-yellow-500'
    default: return 'text-gray-500'
  }
}

function formatTimeAgo(timestamp: string) {
  const date = new Date(timestamp)
  const now = new Date()
  const diffInMs = now.getTime() - date.getTime()
  const diffInMinutes = Math.floor(diffInMs / (1000 * 60))
  const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60))
  const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24))

  if (diffInMinutes < 60) return `${diffInMinutes}m ago`
  if (diffInHours < 24) return `${diffInHours}h ago`
  if (diffInDays < 7) return `${diffInDays}d ago`
  return date.toLocaleDateString()
}
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout>
    <div class="container mx-auto py-6 space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
        <p class="text-muted-foreground">Welcome to CAMR - Centralized Automated Meter Reading</p>
      </div>

      <!-- Statistics Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <!-- Sites Card -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Sites</CardTitle>
            <Building2 class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.sites.total }}</div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground mt-1">
              <Badge variant="outline" class="bg-green-500/10 text-green-600 border-green-500/20">
                {{ props.stats.sites.online }} online
              </Badge>
              <Badge variant="outline" class="bg-red-500/10 text-red-600 border-red-500/20">
                {{ props.stats.sites.offline }} offline
              </Badge>
            </div>
          </CardContent>
        </Card>

        <!-- Gateways Card -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Gateways</CardTitle>
            <Radio class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.gateways.total }}</div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground mt-1">
              <Badge variant="outline" class="bg-green-500/10 text-green-600 border-green-500/20">
                {{ props.stats.gateways.online }} online
              </Badge>
              <Badge variant="outline" class="bg-red-500/10 text-red-600 border-red-500/20">
                {{ props.stats.gateways.offline }} offline
              </Badge>
            </div>
          </CardContent>
        </Card>

        <!-- Meters Card -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Meters</CardTitle>
            <Zap class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.meters.total }}</div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground mt-1">
              <Badge variant="outline" class="bg-green-500/10 text-green-600 border-green-500/20">
                {{ props.stats.meters.active }} active
              </Badge>
              <Badge variant="outline" class="bg-gray-500/10 text-gray-600 border-gray-500/20">
                {{ props.stats.meters.inactive }} inactive
              </Badge>
            </div>
          </CardContent>
        </Card>

        <!-- Resources Card -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Resources</CardTitle>
            <TrendingUp class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="space-y-1">
              <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Buildings</span>
                <span class="font-medium">{{ props.stats.buildings }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Locations</span>
                <span class="font-medium">{{ props.stats.locations }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Config Files</span>
                <span class="font-medium">{{ props.stats.config_files }}</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Energy Consumption Trend -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <BarChart3 class="h-5 w-5" />
            Energy Consumption Trend
          </CardTitle>
          <CardDescription>Average power consumption over the last 30 days (kW)</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="chartData.length === 0" class="text-center py-8 text-muted-foreground">
            <Activity class="h-8 w-8 mx-auto mb-2 opacity-50" />
            <p>No consumption data available</p>
          </div>
          <SimpleLineChart v-else :data="chartData" :height="250" color="#3b82f6" />
        </CardContent>
      </Card>

      <div class="grid gap-6 lg:grid-cols-2">
        <!-- Quick Actions -->
        <Card>
          <CardHeader>
            <CardTitle>Quick Actions</CardTitle>
            <CardDescription>Quickly access common tasks</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid gap-3">
              <Link :href="sites.create().url">
                <Button variant="outline" class="w-full justify-start">
                  <Building2 class="mr-2 h-4 w-4" />
                  Add New Site
                </Button>
              </Link>
              <Link :href="gateways.create().url">
                <Button variant="outline" class="w-full justify-start">
                  <Radio class="mr-2 h-4 w-4" />
                  Add New Gateway
                </Button>
              </Link>
              <Link :href="meters.create().url">
                <Button variant="outline" class="w-full justify-start">
                  <Zap class="mr-2 h-4 w-4" />
                  Add New Meter
                </Button>
              </Link>
              <Link :href="buildings.create().url">
                <Button variant="outline" class="w-full justify-start">
                  <Building2 class="mr-2 h-4 w-4" />
                  Add New Building
                </Button>
              </Link>
              <Link :href="locations.create().url">
                <Button variant="outline" class="w-full justify-start">
                  <MapPin class="mr-2 h-4 w-4" />
                  Add New Location
                </Button>
              </Link>
              <Link :href="configFiles.create().url">
                <Button variant="outline" class="w-full justify-start">
                  <FileCode class="mr-2 h-4 w-4" />
                  Add Config File
                </Button>
              </Link>
              <Link :href="users.create().url">
                <Button variant="outline" class="w-full justify-start">
                  <Users class="mr-2 h-4 w-4" />
                  Add New User
                </Button>
              </Link>
            </div>
          </CardContent>
        </Card>

        <!-- Top Consuming Meters -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <TrendingUp class="h-5 w-5" />
              Top Consuming Meters
            </CardTitle>
            <CardDescription>Highest energy consumption (last 7 days)</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="props.topMeters.length === 0" class="text-center py-8 text-muted-foreground">
              <Zap class="h-8 w-8 mx-auto mb-2 opacity-50" />
              <p>No meter data available</p>
            </div>
            <div v-else class="space-y-3">
              <div 
                v-for="(meter, index) in props.topMeters" 
                :key="meter.name"
                class="flex items-center justify-between p-3 rounded-lg bg-muted/50"
              >
                <div class="flex items-center gap-3">
                  <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold">
                    {{ index + 1 }}
                  </div>
                  <div>
                    <div class="font-medium">{{ meter.name }}</div>
                    <div class="text-xs text-muted-foreground">Energy meter</div>
                  </div>
                </div>
                <div class="text-right">
                  <div class="font-bold">{{ meter.consumption }}</div>
                  <div class="text-xs text-muted-foreground">kWh</div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Recent Activity -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Clock class="h-5 w-5" />
              Recent Activity
            </CardTitle>
            <CardDescription>Latest changes and additions to the system</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-if="props.recentActivity.length === 0" class="text-center py-8 text-muted-foreground">
                <Activity class="h-8 w-8 mx-auto mb-2 opacity-50" />
                <p>No recent activity</p>
              </div>
              <a 
                v-for="(activity, index) in props.recentActivity" 
                :key="index"
                :href="activity.url"
                class="flex items-start gap-3 p-3 rounded-lg hover:bg-accent transition-colors"
              >
                <component 
                  :is="getActivityIcon(activity.type)" 
                  :class="['h-5 w-5 mt-0.5', getActivityColor(activity.type)]"
                />
                <div class="flex-1 space-y-1">
                  <p class="text-sm font-medium leading-none">{{ activity.description }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ formatTimeAgo(activity.timestamp) }}
                  </p>
                </div>
              </a>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- System Status Alert (if there are offline items) -->
      <Card v-if="props.stats.sites.offline > 0 || props.stats.gateways.offline > 0" class="border-yellow-500/50 bg-yellow-500/5">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-yellow-700 dark:text-yellow-500">
            <AlertCircle class="h-5 w-5" />
            System Status Alert
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-2 text-sm">
            <p v-if="props.stats.sites.offline > 0" class="text-muted-foreground">
              <strong>{{ props.stats.sites.offline }}</strong> site(s) are currently offline
            </p>
            <p v-if="props.stats.gateways.offline > 0" class="text-muted-foreground">
              <strong>{{ props.stats.gateways.offline }}</strong> gateway(s) are currently offline
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
