<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useConfirmDialogState } from '@/composables/useConfirmDialog';

const { state, confirm, cancel } = useConfirmDialogState();

const open = computed({
    get: () => state.value.open,
    set: (value: boolean) => {
        if (!value) {
            cancel();
        }
    },
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="sm:max-w-md"
            :show-close-button="false"
            @escape-key-down="cancel"
            @pointer-down-outside="cancel"
        >
            <DialogHeader>
                <DialogTitle>{{ state.title }}</DialogTitle>
                <DialogDescription v-if="state.description">
                    {{ state.description }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2 sm:justify-end">
                <Button
                    v-if="!state.alertOnly"
                    type="button"
                    variant="outline"
                    @click="cancel"
                >
                    {{ state.cancelLabel }}
                </Button>
                <Button type="button" :variant="state.variant" @click="confirm">
                    {{ state.confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
