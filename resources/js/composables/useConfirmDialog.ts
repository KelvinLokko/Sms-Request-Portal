import { readonly, ref } from 'vue';

export type ConfirmDialogVariant = 'default' | 'destructive';

export type ConfirmDialogOptions = {
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: ConfirmDialogVariant;
    /** When true, only the confirm button is shown (replaces window.alert). */
    alertOnly?: boolean;
};

type ConfirmDialogState = ConfirmDialogOptions & {
    open: boolean;
};

type Resolver = (confirmed: boolean) => void;

const state = ref<ConfirmDialogState>({
    open: false,
    title: '',
    description: '',
    confirmLabel: 'Continue',
    cancelLabel: 'Cancel',
    variant: 'default',
    alertOnly: false,
});

let resolver: Resolver | null = null;

function close(confirmed: boolean) {
    state.value = {
        ...state.value,
        open: false,
    };

    resolver?.(confirmed);
    resolver = null;
}

function openDialog(options: ConfirmDialogOptions): Promise<boolean> {
    if (resolver) {
        resolver(false);
        resolver = null;
    }

    state.value = {
        open: true,
        title: options.title,
        description: options.description ?? '',
        confirmLabel:
            options.confirmLabel ?? (options.alertOnly ? 'OK' : 'Continue'),
        cancelLabel: options.cancelLabel ?? 'Cancel',
        variant: options.variant ?? 'default',
        alertOnly: options.alertOnly ?? false,
    };

    return new Promise<boolean>((resolve) => {
        resolver = resolve;
    });
}

export function confirmDialog(options: ConfirmDialogOptions): Promise<boolean> {
    return openDialog({
        ...options,
        alertOnly: false,
    });
}

export function alertDialog(
    options: Omit<ConfirmDialogOptions, 'alertOnly' | 'cancelLabel'>,
): Promise<void> {
    return openDialog({
        ...options,
        alertOnly: true,
        confirmLabel: options.confirmLabel ?? 'OK',
    }).then(() => undefined);
}

export function useConfirmDialogState() {
    return {
        state: readonly(state),
        confirm: () => close(true),
        cancel: () => close(false),
    };
}
