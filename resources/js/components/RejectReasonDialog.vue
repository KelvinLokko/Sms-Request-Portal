<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const open = defineModel<boolean>('open', { default: false });

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        confirmLabel?: string;
        initialReason?: string;
    }>(),
    {
        title: 'Reject request',
        description:
            'Provide a clear reason. This will be shown to the client.',
        confirmLabel: 'Reject',
        initialReason: '',
    },
);

const emit = defineEmits<{
    confirm: [reason: string];
}>();

const reason = ref(props.initialReason);

watch(open, (isOpen) => {
    if (isOpen) {
        reason.value = props.initialReason;
    }
});

function cancel() {
    open.value = false;
}

function confirm() {
    const value = reason.value.trim();

    if (!value) {
        return;
    }

    emit('confirm', value);
    open.value = false;
    reason.value = '';
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <div class="grid gap-2">
                <Label for="reject-reason">Reason</Label>
                <textarea
                    id="reject-reason"
                    v-model="reason"
                    rows="4"
                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    placeholder="Enter rejection reason…"
                />
            </div>

            <DialogFooter class="gap-2 sm:justify-end">
                <Button type="button" variant="outline" @click="cancel">
                    Cancel
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    :disabled="!reason.trim()"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
