<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import RealTimePower from '@/components/analytics/RealTimePower.vue'
import EnergyTrend from '@/components/analytics/EnergyTrend.vue'
import PowerQuality from '@/components/analytics/PowerQuality.vue'
import DemandAnalysis from '@/components/analytics/DemandAnalysis.vue'
import SiteComparison from '@/components/analytics/SiteComparison.vue'
import TopConsumers from '@/components/analytics/TopConsumers.vue'
import { 
  Activity, 
  TrendingUp, 
  Zap, 
  BarChart3, 
  PieChart, 
  Award 
} from 'lucide-vue-next'

const breadcrumbs = [
  { label: 'Dashboard', url: '/dashboard' },
  { label: 'Analytics', url: '/analytics' },
]
</script>

<template>
  <Head title="Analytics" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto py-6 space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Analytics Dashboard</h1>
        <p class="text-muted-foreground">
          Comprehensive energy monitoring and data visualization
        </p>
      </div>

      <!-- Tabs for different sections -->
      <Tabs default-value="overview" class="space-y-6">
        <TabsList class="grid w-full grid-cols-6 lg:w-auto">
          <TabsTrigger value="overview" class="flex items-center gap-2">
            <Activity class="h-4 w-4" />
            <span class="hidden sm:inline">Overview</span>
          </TabsTrigger>
          <TabsTrigger value="trends" class="flex items-center gap-2">
            <TrendingUp class="h-4 w-4" />
            <span class="hidden sm:inline">Trends</span>
          </TabsTrigger>
          <TabsTrigger value="quality" class="flex items-center gap-2">
            <Zap class="h-4 w-4" />
            <span class="hidden sm:inline">Quality</span>
          </TabsTrigger>
          <TabsTrigger value="demand" class="flex items-center gap-2">
            <BarChart3 class="h-4 w-4" />
            <span class="hidden sm:inline">Demand</span>
          </TabsTrigger>
          <TabsTrigger value="sites" class="flex items-center gap-2">
            <PieChart class="h-4 w-4" />
            <span class="hidden sm:inline">Sites</span>
          </TabsTrigger>
          <TabsTrigger value="consumers" class="flex items-center gap-2">
            <Award class="h-4 w-4" />
            <span class="hidden sm:inline">Top</span>
          </TabsTrigger>
        </TabsList>

        <!-- Overview Tab -->
        <TabsContent value="overview" class="space-y-6">
          <div class="grid gap-6 md:grid-cols-2">
            <RealTimePower />
            <TopConsumers :limit="5" />
          </div>
          <EnergyTrend :default-period="'daily'" :days="7" />
        </TabsContent>

        <!-- Energy Trends Tab -->
        <TabsContent value="trends" class="space-y-6">
          <EnergyTrend />
        </TabsContent>

        <!-- Power Quality Tab -->
        <TabsContent value="quality" class="space-y-6">
          <PowerQuality />
        </TabsContent>

        <!-- Demand Analysis Tab -->
        <TabsContent value="demand" class="space-y-6">
          <DemandAnalysis />
        </TabsContent>

        <!-- Site Comparison Tab -->
        <TabsContent value="sites" class="space-y-6">
          <SiteComparison />
        </TabsContent>

        <!-- Top Consumers Tab -->
        <TabsContent value="consumers" class="space-y-6">
          <TopConsumers />
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
