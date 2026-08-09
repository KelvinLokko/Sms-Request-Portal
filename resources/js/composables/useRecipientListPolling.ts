import { usePoll } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import type { Ref } from 'vue';

const PENDING_STATUSES = new Set(['pending', 'processing']);

/**
 * Quietly reload campaign props while a recipient list is still validating.
 */
export function useRecipientListPolling(
    status: Ref<string | null | undefined>,
    options: {
        only?: string[];
        intervalMs?: number;
    } = {},
): void {
    const only = options.only ?? ['campaign', 'can'];
    const intervalMs = options.intervalMs ?? 2500;

    const { start, stop } = usePoll(intervalMs, { only }, { autoStart: false });

    function sync(next: string | null | undefined) {
        if (next && PENDING_STATUSES.has(next)) {
            start();

            return;
        }

        stop();
    }

    onMounted(() => sync(status.value));
    watch(status, sync);
}
