<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { estimate, index, store } from '@/routes/campaigns';

const props = defineProps<{
    senderIds: { id: number; value: string }[];
    maxMessageLength: number;
    warnThreshold: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Campaigns', href: index() },
            { title: 'New', href: '#' },
        ],
    },
});

const messageBody = ref('');
const encoding = ref('text');
const estimateResult = ref<{
    pages: number;
    character_count: number;
    exceeds_621_warning: boolean;
    formatted_cost: string;
    requires_manual_cost_review: boolean;
} | null>(null);

const remaining = computed(
    () => props.maxMessageLength - messageBody.value.length,
);

let estimateTimer: ReturnType<typeof setTimeout> | null = null;

watch([messageBody, encoding], () => {
    if (estimateTimer) {
        clearTimeout(estimateTimer);
    }
    estimateTimer = setTimeout(async () => {
        if (!messageBody.value) {
            estimateResult.value = null;
            return;
        }
        const response = await fetch(estimate.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find((row) => row.startsWith('XSRF-TOKEN='))
                        ?.split('=')[1] ?? '',
                ),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                message_body: messageBody.value,
                billable_recipients: 0,
                encoding: encoding.value,
            }),
        });
        if (response.ok) {
            estimateResult.value = await response.json();
        }
    }, 300);
});
</script>

<template>
    <Head title="New campaign" />

    <div class="mx-auto flex max-w-2xl flex-col gap-6 p-4">
        <Heading
            title="New campaign request"
            description="Compose your message and choose a sender ID. Upload recipients on the next screen."
        />

        <Form
            v-bind="store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Internal name (optional)</Label>
                <Input id="name" name="name" placeholder="August promo" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="sender_id_id">Sender ID</Label>
                <select
                    id="sender_id_id"
                    name="sender_id_id"
                    class="h-9 rounded-md border bg-background px-3 text-sm"
                >
                    <option value="">Select approved sender ID</option>
                    <option
                        v-for="sender in senderIds"
                        :key="sender.id"
                        :value="sender.id"
                    >
                        {{ sender.value }}
                    </option>
                </select>
                <InputError :message="errors.sender_id_id" />
                <p
                    v-if="senderIds.length === 0"
                    class="text-xs text-muted-foreground"
                >
                    No approved sender IDs yet — register one first.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="message_body">Message</Label>
                <textarea
                    id="message_body"
                    name="message_body"
                    v-model="messageBody"
                    rows="6"
                    class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                    :maxlength="maxMessageLength"
                    required
                />
                <div class="flex flex-wrap justify-between gap-2 text-xs text-muted-foreground">
                    <span>{{ remaining }} characters left</span>
                    <span v-if="estimateResult">
                        {{ estimateResult.character_count }} chars ·
                        {{ estimateResult.pages }}
                        {{ estimateResult.pages === 1 ? 'page' : 'pages' }}
                    </span>
                </div>
                <p
                    v-if="estimateResult?.exceeds_621_warning"
                    class="text-sm text-amber-700 dark:text-amber-300"
                >
                    Messages over {{ warnThreshold }} characters sit in a range
                    where gateway behaviour can disagree — consider shortening.
                </p>
                <InputError :message="errors.message_body" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="encoding">Encoding</Label>
                    <select
                        id="encoding"
                        name="encoding"
                        v-model="encoding"
                        class="h-9 rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="text">Text (GSM)</option>
                        <option value="unicode">Unicode</option>
                    </select>
                    <p
                        v-if="encoding === 'unicode'"
                        class="text-xs text-amber-700 dark:text-amber-300"
                    >
                        Unicode campaigns require manual cost review.
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="flash_type">Message type</Label>
                    <select
                        id="flash_type"
                        name="flash_type"
                        class="h-9 rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="text">Standard</option>
                        <option value="flash">Flash</option>
                    </select>
                </div>
            </div>

            <label class="flex items-start gap-2 text-sm">
                <input
                    type="checkbox"
                    name="is_personalised"
                    value="1"
                    class="mt-1"
                />
                <span>
                    Personalised message (uses
                    <code class="rounded bg-muted px-1">[HEADER]</code>
                    placeholders)
                </span>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="requested_send_at">Requested send time</Label>
                    <Input
                        id="requested_send_at"
                        name="requested_send_at"
                        type="datetime-local"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="hard_deadline_at">Hard deadline</Label>
                    <Input
                        id="hard_deadline_at"
                        name="hard_deadline_at"
                        type="datetime-local"
                    />
                </div>
            </div>

            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" />
                Save draft
            </Button>
        </Form>
    </div>
</template>
