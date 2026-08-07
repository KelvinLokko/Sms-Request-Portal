<script setup lang="ts">
/**
 * Invisible honeypot — real users never see or fill this.
 * Bots that auto-fill every field get rejected server-side.
 */
</script>

<template>
    <div
        aria-hidden="true"
        class="pointer-events-none absolute -left-[9999px] h-0 w-0 overflow-hidden opacity-0"
        tabindex="-1"
    >
        <label for="website">Website</label>
        <input
            id="website"
            type="text"
            name="website"
            autocomplete="off"
            tabindex="-1"
        />
    </div>
</template>
