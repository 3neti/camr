<script setup lang="ts">
import { ref } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import { Toaster } from '@/components/ui/sonner';
import { useFlash } from '@/composables/useFlash';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const searchRef = ref<InstanceType<typeof GlobalSearch> | null>(null);

function openSearch() {
    searchRef.value?.open();
}

// Initialize flash message handling
useFlash();
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" @search="openSearch" />
            <slot />
        </AppContent>
        <GlobalSearch ref="searchRef" />
        <Toaster position="top-right" />
    </AppShell>
</template>
