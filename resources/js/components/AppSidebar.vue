<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, analytics } from '@/routes';
import * as sites from '@/actions/App/Http/Controllers/SiteController';
import * as buildings from '@/actions/App/Http/Controllers/BuildingController';
import * as locations from '@/actions/App/Http/Controllers/LocationController';
import * as gateways from '@/actions/App/Http/Controllers/GatewayController';
import * as meters from '@/actions/App/Http/Controllers/MeterController';
import * as configFiles from '@/actions/App/Http/Controllers/ConfigurationFileController';
import * as reports from '@/actions/App/Http/Controllers/ReportsController';
import * as users from '@/actions/App/Http/Controllers/UserController';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, MapPin, Radio, Zap, BarChart3, Users, Building2, FileCode, TrendingUp } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Analytics',
        href: analytics(),
        icon: TrendingUp,
    },
    {
        title: 'Live Monitoring',
        href: '/live-monitoring',
        icon: Radio,
    },
    {
        title: 'Sites',
        href: sites.index().url,
        icon: MapPin,
    },
    {
        title: 'Buildings',
        href: buildings.index().url,
        icon: Building2,
    },
    {
        title: 'Locations',
        href: locations.index().url,
        icon: MapPin,
    },
    {
        title: 'Gateways',
        href: gateways.index().url,
        icon: Radio,
    },
    {
        title: 'Meters',
        href: meters.index().url,
        icon: Zap,
    },
    {
        title: 'Config Files',
        href: configFiles.index().url,
        icon: FileCode,
    },
    {
        title: 'Reports',
        href: reports.index().url,
        icon: BarChart3,
    },
    {
        title: 'Users',
        href: users.index().url,
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/3neti/camr',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://github.com/3neti/camr/blob/main/README.md',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
