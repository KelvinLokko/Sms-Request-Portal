<script setup lang="ts">
import ConfirmDialogHost from '@/components/ConfirmDialogHost.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
        <ConfirmDialogHost />
    </AppLayout>
</template>
