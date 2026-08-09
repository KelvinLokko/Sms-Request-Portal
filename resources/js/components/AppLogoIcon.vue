<script setup lang="ts">
defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <img
        src="/images/favicon.png"
        alt=""
        width="40"
        height="40"
        decoding="async"
        v-bind="$attrs"
    />
</template>
