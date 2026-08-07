<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        siteKey: string | null;
        /** When false, only emit the token (use for AJAX OTP send). */
        includeHiddenInput?: boolean;
        /** Reset the widget when this value changes (e.g. after failed submit). */
        resetKey?: number | string;
    }>(),
    {
        includeHiddenInput: true,
    },
);

const emit = defineEmits<{
    token: [value: string];
    expired: [];
    error: [];
}>();

const container = ref<HTMLElement | null>(null);
const widgetId = ref<string | number | null>(null);
const token = ref('');

declare global {
    interface Window {
        turnstile?: {
            render: (
                element: HTMLElement,
                options: Record<string, unknown>,
            ) => string | number;
            reset: (widgetId?: string | number) => void;
            remove: (widgetId?: string | number) => void;
        };
        onTurnstileLoad?: () => void;
    }
}

function loadScript(): Promise<void> {
    if (window.turnstile) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const existing = document.querySelector<HTMLScriptElement>(
            'script[data-turnstile]',
        );

        if (existing) {
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener(
                'error',
                () => reject(new Error('Turnstile failed to load')),
                { once: true },
            );

            return;
        }

        window.onTurnstileLoad = () => resolve();

        const script = document.createElement('script');
        script.src =
            'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad';
        script.async = true;
        script.defer = true;
        script.dataset.turnstile = 'true';
        script.onerror = () => reject(new Error('Turnstile failed to load'));
        document.head.appendChild(script);
    });
}

async function renderWidget(): Promise<void> {
    if (!props.siteKey || !container.value) {
        return;
    }

    await loadScript();

    if (!window.turnstile || !container.value) {
        return;
    }

    if (widgetId.value !== null) {
        window.turnstile.remove(widgetId.value);
        widgetId.value = null;
    }

    container.value.innerHTML = '';

    widgetId.value = window.turnstile.render(container.value, {
        sitekey: props.siteKey,
        theme: 'light',
        callback: (value: string) => {
            token.value = value;
            emit('token', value);
        },
        'expired-callback': () => {
            token.value = '';
            emit('expired');
        },
        'error-callback': () => {
            token.value = '';
            emit('error');
        },
    });
}

function reset(): void {
    token.value = '';

    if (widgetId.value !== null && window.turnstile) {
        window.turnstile.reset(widgetId.value);
    }
}

watch(
    () => props.resetKey,
    () => reset(),
);

onMounted(() => {
    void renderWidget();
});

onBeforeUnmount(() => {
    if (widgetId.value !== null && window.turnstile) {
        window.turnstile.remove(widgetId.value);
    }
});

defineExpose({ reset, token });
</script>

<template>
    <div v-if="siteKey" class="space-y-2">
        <div ref="container" class="cf-turnstile" />
        <input
            v-if="includeHiddenInput"
            type="hidden"
            name="cf-turnstile-response"
            :value="token"
        />
    </div>
</template>
