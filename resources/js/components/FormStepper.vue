<script setup lang="ts">
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import type { WizardStep } from '@/composables/useFormWizard';

const props = defineProps<{
    steps: WizardStep[];
    currentIndex: number;
}>();

const emit = defineEmits<{
    select: [index: number];
}>();

const current = computed(() => props.steps[props.currentIndex]);
const progress = computed(
    () => ((props.currentIndex + 1) / props.steps.length) * 100,
);

function statusOf(index: number): 'complete' | 'current' | 'upcoming' {
    if (index < props.currentIndex) {
        return 'complete';
    }

    return index === props.currentIndex ? 'current' : 'upcoming';
}
</script>

<template>
    <nav
        :aria-label="`Form progress, step ${currentIndex + 1} of ${steps.length}`"
    >
        <!-- Compact progress for narrow screens -->
        <div class="sm:hidden">
            <div class="flex items-baseline justify-between gap-3">
                <p class="text-sm font-medium text-foreground">
                    {{ current.title }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Step {{ currentIndex + 1 }} of {{ steps.length }}
                </p>
            </div>
            <div
                class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted"
                role="progressbar"
                :aria-valuenow="currentIndex + 1"
                aria-valuemin="1"
                :aria-valuemax="steps.length"
                :aria-valuetext="`Step ${currentIndex + 1} of ${steps.length}: ${current.title}`"
            >
                <div
                    class="h-full rounded-full bg-primary transition-[width] duration-500 ease-out"
                    :style="{ width: `${progress}%` }"
                />
            </div>
            <p
                v-if="current.description"
                class="mt-2 text-xs text-muted-foreground"
            >
                {{ current.description }}
            </p>
        </div>

        <!-- Full stepper from small screens up -->
        <ol class="hidden gap-2 sm:flex">
            <li
                v-for="(step, index) in steps"
                :key="step.id"
                class="flex flex-1 flex-col gap-2"
            >
                <span
                    class="h-1 rounded-full transition-colors duration-300"
                    :class="index <= currentIndex ? 'bg-primary' : 'bg-muted'"
                    aria-hidden="true"
                />
                <component
                    :is="statusOf(index) === 'complete' ? 'button' : 'div'"
                    :type="
                        statusOf(index) === 'complete' ? 'button' : undefined
                    "
                    class="flex items-start gap-2 rounded-md text-left"
                    :class="
                        statusOf(index) === 'complete'
                            ? 'cursor-pointer hover:opacity-80'
                            : ''
                    "
                    :aria-current="
                        statusOf(index) === 'current' ? 'step' : undefined
                    "
                    @click="
                        statusOf(index) === 'complete'
                            ? emit('select', index)
                            : undefined
                    "
                >
                    <span
                        class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border text-[0.65rem] font-semibold transition-colors duration-300"
                        :class="{
                            'border-primary bg-primary text-primary-foreground':
                                statusOf(index) !== 'upcoming',
                            'border-border text-muted-foreground':
                                statusOf(index) === 'upcoming',
                        }"
                    >
                        <Check
                            v-if="statusOf(index) === 'complete'"
                            class="size-3"
                            aria-hidden="true"
                        />
                        <template v-else>{{ index + 1 }}</template>
                    </span>
                    <span class="min-w-0">
                        <span
                            class="block truncate text-sm font-medium"
                            :class="
                                statusOf(index) === 'upcoming'
                                    ? 'text-muted-foreground'
                                    : 'text-foreground'
                            "
                        >
                            {{ step.title }}
                        </span>
                        <span class="sr-only">
                            {{
                                statusOf(index) === 'complete'
                                    ? 'Completed, select to revisit'
                                    : statusOf(index) === 'current'
                                      ? 'Current step'
                                      : 'Not yet reached'
                            }}
                        </span>
                    </span>
                </component>
            </li>
        </ol>
    </nav>
</template>
