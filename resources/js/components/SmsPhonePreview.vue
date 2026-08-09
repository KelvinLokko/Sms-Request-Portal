<script setup lang="ts">
import {
    ArrowLeft,
    Check,
    ChevronLeft,
    ChevronRight,
    EllipsisVertical,
    Image as ImageIcon,
    Mic,
    Phone,
    Plus,
    Search,
    Video,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        sender?: string | null;
        message?: string | null;
        maxLength?: number;
        showToggle?: boolean;
        class?: string;
    }>(),
    {
        sender: '',
        message: '',
        maxLength: 160,
        showToggle: true,
    },
);

const platform = ref<'ios' | 'android'>('ios');

const displaySender = computed(() => {
    const value = props.sender?.trim() ?? '';

    if (!value || value === 'Not selected') {
        return 'Sender';
    }

    return value;
});

const initial = computed(
    () => displaySender.value.charAt(0).toUpperCase() || 'S',
);

const displayMessage = computed(() => props.message?.trim() ?? '');
const hasMessage = computed(() => displayMessage.value.length > 0);
const characterCount = computed(() => (props.message ?? '').length);

const timestampLabel = computed(() => {
    const now = new Date();
    const hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const period = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;

    return `Today ${hour12}:${minutes} ${period}`;
});
</script>

<template>
    <div :class="cn('flex w-full flex-col items-center gap-4', props.class)">
        <div
            v-if="showToggle"
            class="inline-flex items-center rounded-md bg-muted p-0.5 text-xs font-medium"
            role="group"
            aria-label="Preview platform"
        >
            <button
                type="button"
                class="rounded-sm px-3 py-1 transition-colors"
                :class="
                    platform === 'ios'
                        ? 'bg-background text-foreground shadow-xs'
                        : 'text-muted-foreground hover:text-foreground'
                "
                :aria-pressed="platform === 'ios'"
                @click="platform = 'ios'"
            >
                iOS
            </button>
            <button
                type="button"
                class="rounded-sm px-3 py-1 transition-colors"
                :class="
                    platform === 'android'
                        ? 'bg-background text-foreground shadow-xs'
                        : 'text-muted-foreground hover:text-foreground'
                "
                :aria-pressed="platform === 'android'"
                @click="platform = 'android'"
            >
                Android
            </button>
        </div>

        <!-- iOS -->
        <div
            v-if="platform === 'ios'"
            class="w-full max-w-[300px] rounded-[2.75rem] bg-[#d5d5d7] p-[3px] shadow-lg"
            :aria-label="`iPhone preview of the message from ${displaySender}`"
        >
            <div class="rounded-[2.6rem] bg-[#1c1c1e] p-[9px]">
                <div
                    class="flex aspect-[9/19] flex-col overflow-hidden rounded-[2.1rem] bg-white text-[#000]"
                >
                    <!-- status bar -->
                    <div
                        class="flex shrink-0 items-center justify-between px-5 pt-3 pb-1"
                    >
                        <span class="text-[13px] font-semibold tracking-tight">
                            9:41
                        </span>
                        <div class="flex items-center gap-[5px]">
                            <svg
                                viewBox="0 0 18 12"
                                class="h-[9px] w-[15px] fill-current"
                                aria-hidden="true"
                            >
                                <rect x="0" y="8" width="3" height="4" rx="1" />
                                <rect x="5" y="6" width="3" height="6" rx="1" />
                                <rect
                                    x="10"
                                    y="3"
                                    width="3"
                                    height="9"
                                    rx="1"
                                />
                                <rect
                                    x="15"
                                    y="0"
                                    width="3"
                                    height="12"
                                    rx="1"
                                />
                            </svg>
                            <svg
                                viewBox="0 0 16 12"
                                class="h-[10px] w-[13px] fill-current"
                                aria-hidden="true"
                            >
                                <path
                                    d="M8 11.4 5.7 9a3.3 3.3 0 0 1 4.6 0L8 11.4Z"
                                />
                                <path
                                    d="M8 6c1.5 0 2.9.6 3.9 1.6l1.3-1.4A7.6 7.6 0 0 0 8 4a7.6 7.6 0 0 0-5.2 2.2l1.3 1.4A5.5 5.5 0 0 1 8 6Z"
                                />
                                <path
                                    d="M8 1.5c2.7 0 5.2 1 7.1 2.8L16 3.2A11.4 11.4 0 0 0 8 0 11.4 11.4 0 0 0 0 3.2l.9 1.1A10.3 10.3 0 0 1 8 1.5Z"
                                />
                            </svg>
                            <svg
                                viewBox="0 0 27 13"
                                class="h-[11px] w-[23px]"
                                aria-hidden="true"
                            >
                                <rect
                                    x="0.6"
                                    y="0.6"
                                    width="21.8"
                                    height="11.8"
                                    rx="3.4"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-opacity="0.35"
                                    stroke-width="1.2"
                                />
                                <rect
                                    x="2.2"
                                    y="2.2"
                                    width="18.6"
                                    height="8.6"
                                    rx="2.2"
                                    fill="currentColor"
                                />
                                <path
                                    d="M24.4 4.4c1.1.3 1.6 1 1.6 2.1s-.5 1.8-1.6 2.1V4.4Z"
                                    fill="currentColor"
                                    fill-opacity="0.35"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- conversation header -->
                    <header
                        class="relative flex shrink-0 flex-col items-center border-b border-[#c9c9cc] bg-[#f6f6f6] px-3 pt-1 pb-2"
                    >
                        <ChevronLeft
                            class="absolute top-4 left-3 size-6 text-[#007aff]"
                            :stroke-width="2.5"
                            aria-hidden="true"
                        />
                        <Video
                            class="absolute top-4 right-3 size-6 text-[#007aff]"
                            :stroke-width="2"
                            aria-hidden="true"
                        />
                        <div
                            class="flex size-9 items-center justify-center rounded-full bg-[#c7c7cc] text-[13px] font-semibold text-white"
                            aria-hidden="true"
                        >
                            {{ initial }}
                        </div>
                        <p
                            class="mt-1 flex max-w-[60%] items-center text-[11px] font-medium text-[#000]"
                        >
                            <span class="truncate">{{ displaySender }}</span>
                            <ChevronRight
                                class="ml-0.5 size-3 shrink-0 text-[#8e8e93]"
                                aria-hidden="true"
                            />
                        </p>
                    </header>

                    <!-- messages -->
                    <div
                        class="preview-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain bg-white px-3 pt-3 pb-2"
                        tabindex="0"
                        aria-label="Message preview"
                    >
                        <p
                            class="mb-3 text-center text-[11px] font-medium text-[#8e8e93]"
                        >
                            {{ timestampLabel }}
                        </p>
                        <div
                            class="ios-bubble relative max-w-[80%] rounded-[18px] bg-[#e9e9eb] px-3 py-2 text-[14px] leading-[1.35] break-words whitespace-pre-wrap"
                        >
                            <template v-if="hasMessage">
                                {{ displayMessage }}
                            </template>
                            <span v-else class="text-[#8e8e93]">
                                Your message will appear here
                            </span>
                        </div>
                    </div>

                    <!-- composer -->
                    <div
                        class="flex shrink-0 items-center gap-2 bg-white px-3 pt-2 pb-4"
                    >
                        <div
                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-[#e9e9eb]"
                            aria-hidden="true"
                        >
                            <Plus class="size-4 text-[#6f6f73]" />
                        </div>
                        <div
                            class="flex flex-1 items-center justify-between rounded-full border border-[#d1d1d6] py-1.5 pr-2 pl-3"
                            aria-hidden="true"
                        >
                            <span class="text-[12px] text-[#a9a9ae]">
                                Text message
                            </span>
                            <Mic class="size-4 text-[#a9a9ae]" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Android -->
        <div
            v-else
            class="w-full max-w-[300px] rounded-[2.25rem] bg-[#d5d5d7] p-[3px] shadow-lg"
            :aria-label="`Android preview of the message from ${displaySender}`"
        >
            <div class="rounded-[2.1rem] bg-[#111] p-[8px]">
                <div
                    class="flex aspect-[9/19] flex-col overflow-hidden rounded-[1.7rem] bg-white text-[#1f1f1f]"
                >
                    <!-- status bar -->
                    <div
                        class="flex shrink-0 items-center justify-between px-4 pt-2.5 pb-2"
                    >
                        <span class="text-[12px]">9:30</span>
                        <div class="flex items-center gap-[6px]">
                            <svg
                                viewBox="0 0 16 12"
                                class="h-[10px] w-[13px] fill-current"
                                aria-hidden="true"
                            >
                                <path
                                    d="M8 11.4 5.7 9a3.3 3.3 0 0 1 4.6 0L8 11.4Z"
                                />
                                <path
                                    d="M8 6c1.5 0 2.9.6 3.9 1.6l1.3-1.4A7.6 7.6 0 0 0 8 4a7.6 7.6 0 0 0-5.2 2.2l1.3 1.4A5.5 5.5 0 0 1 8 6Z"
                                />
                                <path
                                    d="M8 1.5c2.7 0 5.2 1 7.1 2.8L16 3.2A11.4 11.4 0 0 0 8 0 11.4 11.4 0 0 0 0 3.2l.9 1.1A10.3 10.3 0 0 1 8 1.5Z"
                                />
                            </svg>
                            <svg
                                viewBox="0 0 14 12"
                                class="h-[11px] w-[12px] fill-current"
                                aria-hidden="true"
                            >
                                <path d="M13 0v12H1L13 0Z" />
                            </svg>
                        </div>
                    </div>

                    <!-- conversation header -->
                    <header
                        class="flex shrink-0 items-center gap-2.5 bg-[#f5f5f5] px-3 py-2.5"
                    >
                        <ArrowLeft
                            class="size-[18px] shrink-0"
                            :stroke-width="2.25"
                            aria-hidden="true"
                        />
                        <div
                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-[#dadce0] text-[12px] font-semibold text-[#5f6368]"
                            aria-hidden="true"
                        >
                            {{ initial }}
                        </div>
                        <p
                            class="min-w-0 flex-1 truncate text-[15px] font-medium"
                        >
                            {{ displaySender }}
                        </p>
                        <div
                            class="flex shrink-0 items-center gap-2.5"
                            aria-hidden="true"
                        >
                            <Video class="size-[17px]" :stroke-width="2.25" />
                            <Phone class="size-[15px]" :stroke-width="2.25" />
                            <Search class="size-[15px]" :stroke-width="2.25" />
                            <EllipsisVertical
                                class="size-[15px]"
                                :stroke-width="2.25"
                            />
                        </div>
                    </header>

                    <!-- messages -->
                    <div
                        class="preview-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain bg-white px-3 pt-3 pb-2"
                        tabindex="0"
                        aria-label="Message preview"
                    >
                        <div class="mb-3 flex justify-center">
                            <span
                                class="rounded-full bg-[#f1f3f4] px-3 py-1 text-[11px] text-[#5f6368]"
                            >
                                {{ timestampLabel }}
                            </span>
                        </div>
                        <div class="flex items-end gap-2">
                            <div
                                class="flex size-6 shrink-0 items-center justify-center rounded-full bg-[#dadce0] text-[10px] font-semibold text-[#5f6368]"
                                aria-hidden="true"
                            >
                                {{ initial }}
                            </div>
                            <div
                                class="max-w-[78%] rounded-[18px] bg-[#e4e6eb] px-3.5 py-2.5 text-[14px] leading-[1.35] break-words whitespace-pre-wrap"
                            >
                                <template v-if="hasMessage">
                                    {{ displayMessage }}
                                </template>
                                <span v-else class="text-[#80868b]">
                                    Your message will appear here
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- composer -->
                    <div
                        class="flex shrink-0 items-center gap-2.5 bg-[#f5f5f5] px-3 py-3"
                        aria-hidden="true"
                    >
                        <Plus class="size-[18px]" :stroke-width="2.25" />
                        <ImageIcon class="size-[17px]" :stroke-width="2.25" />
                        <div
                            class="flex-1 rounded-full bg-white px-3 py-1.5 text-[12px] text-[#9aa0a6]"
                        >
                            Text message
                        </div>
                        <div
                            class="flex size-[18px] items-center justify-center rounded-full bg-[#1f1f1f]"
                        >
                            <Check
                                class="size-[11px] text-white"
                                :stroke-width="3"
                            />
                        </div>
                        <Mic class="size-[15px]" :stroke-width="2.25" />
                    </div>
                </div>
            </div>
        </div>

        <p class="text-xs text-muted-foreground tabular-nums">
            {{ characterCount }} / {{ maxLength }} characters
        </p>
    </div>
</template>

<style scoped>
/* Classic iMessage tail on the incoming bubble. */
.ios-bubble::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: -6px;
    width: 18px;
    height: 18px;
    background: #e9e9eb;
    border-bottom-right-radius: 16px;
}

.ios-bubble::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: -10px;
    width: 10px;
    height: 18px;
    background: #fff;
    border-bottom-right-radius: 10px;
}

.preview-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.25) transparent;
}

.preview-scroll::-webkit-scrollbar {
    width: 4px;
}

.preview-scroll::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.25);
}

.preview-scroll::-webkit-scrollbar-track {
    background: transparent;
}
</style>
