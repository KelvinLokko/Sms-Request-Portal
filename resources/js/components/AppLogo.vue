<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <AppLogoIcon
        class="hidden size-8 shrink-0 rounded-md object-cover group-data-[collapsible=icon]:block"
    />
    <div class="group-data-[collapsible=icon]:hidden">
        <img
            src="/images/logo.png"
            :alt="name"
            width="316"
            height="69"
            decoding="async"
            class="h-9 w-auto max-w-[14rem] object-contain object-left dark:hidden"
        />
        <img
            src="/images/logo-white-fordark.png"
            :alt="name"
            width="316"
            height="69"
            decoding="async"
            class="hidden h-9 w-auto max-w-[14rem] object-contain object-left dark:block"
        />
    </div>
</template>
