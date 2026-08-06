import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import type { Ref } from 'vue';

export type WizardStep = {
    /** Matches the `data-step` attribute on the step's container element. */
    id: string;
    title: string;
    description?: string;
    /** Field names owned by the step, used to jump back to server-side errors. */
    fields: string[];
};

/**
 * Drives a single Inertia form split across several steps. Every field stays
 * mounted (hidden steps use `v-show`) so the whole form still submits in one
 * request; only the visible step carries native `required` validation.
 */
export function useFormWizard(
    steps: WizardStep[],
    formRef: Ref<HTMLElement | null>,
) {
    const page = usePage();
    const currentIndex = ref(0);

    const current = computed(() => steps[currentIndex.value]);
    const isFirst = computed(() => currentIndex.value === 0);
    const isLast = computed(() => currentIndex.value === steps.length - 1);
    const progress = computed(
        () => ((currentIndex.value + 1) / steps.length) * 100,
    );

    function isActive(id: string): boolean {
        return current.value.id === id;
    }

    function focusStep(): void {
        void nextTick(() => {
            const step = formRef.value?.querySelector<HTMLElement>(
                `[data-step="${current.value.id}"]`,
            );

            step?.querySelector<HTMLElement>('[data-step-heading]')?.focus();
        });
    }

    function goTo(index: number): void {
        currentIndex.value = Math.min(Math.max(index, 0), steps.length - 1);
        focusStep();
    }

    /** Blocks navigation until the visible step passes browser validation. */
    function validateCurrentStep(): boolean {
        const step = formRef.value?.querySelector<HTMLElement>(
            `[data-step="${current.value.id}"]`,
        );

        if (!step) {
            return true;
        }

        const controls = step.querySelectorAll<
            HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
        >('input, select, textarea');

        for (const control of controls) {
            if (!control.checkValidity()) {
                control.reportValidity();

                return false;
            }
        }

        return true;
    }

    function next(): void {
        if (!validateCurrentStep()) {
            return;
        }

        goTo(currentIndex.value + 1);
    }

    function back(): void {
        goTo(currentIndex.value - 1);
    }

    watch(
        () => page.props.errors,
        (errors) => {
            const failed = Object.keys(errors ?? {});

            if (failed.length === 0) {
                return;
            }

            const index = steps.findIndex((step) =>
                step.fields.some((field) => failed.includes(field)),
            );

            if (index !== -1) {
                goTo(index);
            }
        },
        { deep: true },
    );

    return {
        steps,
        current,
        currentIndex,
        isFirst,
        isLast,
        isActive,
        progress,
        goTo,
        next,
        back,
    };
}
