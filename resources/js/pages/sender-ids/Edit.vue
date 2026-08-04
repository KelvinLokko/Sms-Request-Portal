<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SenderIdController from '@/actions/App/Http/Controllers/SenderIdController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/sender-ids';

const props = defineProps<{
    senderId: {
        id: number;
        value: string;
        status: string;
        uses_company_letterhead: boolean;
        has_document: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Sender IDs', href: index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Edit sender ID" />

    <div class="mx-auto flex max-w-lg flex-col gap-6 p-4">
        <Heading
            title="Edit sender ID"
            :description="
                senderId.status === 'approved'
                    ? 'Saving changes will reset this sender ID to pending review.'
                    : 'Update the sender ID and resubmit for review if needed.'
            "
        />

        <Form
            v-bind="SenderIdController.update.form(props.senderId.id)"
            enctype="multipart/form-data"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="value">Sender ID</Label>
                <Input
                    id="value"
                    name="value"
                    required
                    maxlength="11"
                    :default-value="senderId.value"
                />
                <InputError :message="errors.value" />
            </div>

            <div class="grid gap-2">
                <Label for="document">Replace document (optional)</Label>
                <Input
                    id="document"
                    type="file"
                    name="document"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                />
                <p
                    v-if="senderId.has_document"
                    class="text-xs text-muted-foreground"
                >
                    A document is already on file.
                </p>
                <InputError :message="errors.document" />
            </div>

            <label class="flex items-start gap-2 text-sm">
                <input
                    type="checkbox"
                    name="uses_company_letterhead"
                    value="1"
                    class="mt-1"
                    :checked="senderId.uses_company_letterhead"
                />
                <span>Uses company letterhead already on file</span>
            </label>

            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" />
                Save changes
            </Button>
        </Form>
    </div>
</template>
