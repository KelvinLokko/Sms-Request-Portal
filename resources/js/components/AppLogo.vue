<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm shadow-primary/25"
    >
        <AppLogoIcon class="size-5" />
    </div>
    <div class="ml-2 grid min-w-0 flex-1 text-left text-sm group-data-[collapsible=icon]:hidden">
        <span class="truncate leading-tight font-semibold tracking-tight text-foreground">
            {{ name }}
        </span>
        <span class="truncate text-[11px] leading-tight text-muted-foreground">
            Managed SMS requests
        </span>
    </div>
</template>
