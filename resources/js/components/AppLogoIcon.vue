<script setup lang="ts">
defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        fill="none"
        aria-hidden="true"
        v-bind="$attrs"
    >
        <!-- Abstract message / signal mark for SMS Client Portal -->
        <rect
            x="4"
            y="8"
            width="26"
            height="18"
            rx="5"
            fill="currentColor"
            opacity="0.92"
        />
        <path
            d="M12 26.5 16.5 22H27a3 3 0 0 0 3-3V12"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linecap="round"
            stroke-linejoin="round"
            opacity="0.35"
        />
        <path
            d="M12 15.5h10M12 19.5h7"
            stroke="#fff"
            stroke-width="2.2"
            stroke-linecap="round"
        />
        <circle cx="32" cy="11" r="3.5" fill="currentColor" opacity="0.55" />
        <circle cx="32" cy="11" r="1.6" fill="#fff" />
    </svg>
</template>
