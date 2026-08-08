<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const brand = usePage().props.name;
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background lg:flex-row">
        <!-- Mobile brand strip -->
        <div
            class="auth-panel-glow relative flex items-center gap-3 overflow-hidden px-5 py-4 text-white lg:hidden"
        >
            <a
                :href="home.url()"
                class="relative z-10 inline-flex items-center gap-3 transition-opacity hover:opacity-90"
            >
                <span
                    class="flex size-9 items-center justify-center rounded-xl bg-signal text-ink"
                >
                    <AppLogoIcon class="size-5" />
                </span>
                <span class="text-base font-semibold tracking-tight">
                    {{ brand }}
                </span>
            </a>
        </div>

        <!-- Desktop brand panel -->
        <aside
            class="auth-panel-glow relative hidden flex-col justify-between overflow-hidden px-10 py-12 text-white lg:flex lg:w-[46%] lg:max-w-xl lg:px-12"
        >
            <div
                class="pointer-events-none absolute inset-0 opacity-40"
                aria-hidden="true"
            >
                <svg
                    class="h-full w-full"
                    viewBox="0 0 600 800"
                    fill="none"
                    preserveAspectRatio="xMidYMid slice"
                >
                    <circle
                        cx="480"
                        cy="120"
                        r="180"
                        stroke="#C6F24E"
                        stroke-opacity="0.25"
                        stroke-width="1.5"
                    />
                    <circle
                        cx="480"
                        cy="120"
                        r="110"
                        stroke="#ECFCCB"
                        stroke-opacity="0.2"
                        stroke-width="1.5"
                    />
                    <path
                        d="M40 640c120-80 220-40 320-100s180-40 220 20"
                        stroke="#86EFAC"
                        stroke-opacity="0.35"
                        stroke-width="1.5"
                    />
                </svg>
            </div>

            <a
                :href="home.url()"
                class="relative z-10 inline-flex items-center gap-3 self-start transition-opacity hover:opacity-90"
            >
                <span
                    class="flex size-10 items-center justify-center rounded-xl bg-signal text-ink shadow-sm shadow-black/20"
                >
                    <AppLogoIcon class="size-6" />
                </span>
                <span class="text-lg font-semibold tracking-tight">
                    {{ brand }}
                </span>
            </a>

            <div class="relative z-10 max-w-md animate-fade-up">
                <p
                    class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold tracking-wide text-signal uppercase"
                >
                    Managed SMS portal
                </p>
                <h1
                    class="mt-4 text-3xl font-bold tracking-tight text-balance sm:text-4xl"
                >
                    Submit SMS campaigns. We handle the rest.
                </h1>
                <p class="mt-4 text-base leading-relaxed text-white/75">
                    Upload your list, get a clear quote, pay your invoice, and
                    our team sends the campaign for you.
                </p>
            </div>

            <p class="relative z-10 text-sm text-white/50">
                No self-service gateway — fulfilment stays with our operators.
            </p>
        </aside>

        <!-- Form column -->
        <div
            class="flex flex-1 flex-col justify-center px-5 py-8 sm:px-10 lg:px-16 lg:py-12"
        >
            <div class="mx-auto w-full max-w-md animate-fade-up-delay">
                <div class="mb-8 space-y-2">
                    <h2
                        class="text-2xl font-semibold tracking-tight text-foreground"
                    >
                        {{ title }}
                    </h2>
                    <p
                        v-if="description"
                        class="text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
