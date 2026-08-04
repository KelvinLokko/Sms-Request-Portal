import type { Page } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

type FlashProps = {
    success?: string | null;
    error?: string | null;
    toast?: FlashToast | null;
};

function showSessionFlash(flash?: FlashProps | null): void {
    if (!flash) {
        return;
    }

    if (flash.success) {
        toast.success(flash.success);
    }

    if (flash.error) {
        toast.error(flash.error);
    }
}

function showInertiaToast(data?: FlashToast | null): void {
    if (!data?.message) {
        return;
    }

    const show = toast[data.type] ?? toast.message;
    show(data.message);
}

export function initializeFlashToast(): void {
    // Settings / Inertia::flash('toast', …)
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash as
            | FlashProps
            | undefined;

        showInertiaToast(flash?.toast);
    });

    // Controllers using ->with('success'|'error', …) shared as page props
    router.on('success', (event) => {
        const page = (event as CustomEvent<{ page: Page }>).detail?.page;
        const flash = page?.props?.flash as FlashProps | undefined;

        showSessionFlash(flash);
    });
}
