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
import { download as downloadTemplate } from '@/routes/campaigns/templates';

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
const campaignType = ref<'bulk' | 'personalised_bulk'>('bulk');
const selectedFileName = ref('');
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

const templateHref = computed(() =>
    downloadTemplate.url(
        campaignType.value === 'personalised_bulk'
            ? 'personalised-bulk'
            : 'bulk',
    ),
);

let estimateTimer: ReturnType<typeof setTimeout> | null = null;

watch(messageBody, () => {
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
                encoding: 'text',
            }),
        });
        if (response.ok) {
            estimateResult.value = await response.json();
        }
    }, 300);
});

function onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    selectedFileName.value = input.files?.[0]?.name ?? '';
}
</script>

<template>
    <Head title="New campaign" />

    <div class="mx-auto flex max-w-2xl flex-col gap-6 p-4 sm:p-6">
        <Heading
            title="New campaign request"
            description="Choose the campaign type, upload your contacts, then compose the message — all in one step."
        />

        <Form
            v-bind="store.form()"
            enctype="multipart/form-data"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div
                v-if="Object.keys(errors).length"
                class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-800 dark:text-red-200"
                role="alert"
            >
                Please fix the highlighted fields before saving.
            </div>

            <div class="grid gap-2">
                <Label for="name">Internal name (optional)</Label>
                <Input id="name" name="name" placeholder="August promo" />
                <InputError :message="errors.name" />
            </div>

            <fieldset class="grid gap-3">
                <legend class="text-sm font-medium">Campaign type</legend>
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-card p-4 transition hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-accent/40"
                >
                    <input
                        v-model="campaignType"
                        type="radio"
                        name="campaign_type"
                        value="bulk"
                        class="mt-1"
                        required
                    />
                    <span class="space-y-1">
                        <span class="block font-medium">Bulk</span>
                        <span class="block text-sm text-muted-foreground">
                            Same message to every contact. Your file only needs
                            phone numbers.
                        </span>
                    </span>
                </label>
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-card p-4 transition hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-accent/40"
                >
                    <input
                        v-model="campaignType"
                        type="radio"
                        name="campaign_type"
                        value="personalised_bulk"
                        class="mt-1"
                    />
                    <span class="space-y-1">
                        <span class="block font-medium">Personalised bulk</span>
                        <span class="block text-sm text-muted-foreground">
                            Message can include
                            <code class="rounded bg-muted px-1">[Name]</code>
                            style placeholders. Extra columns in your file fill
                            those values per contact.
                        </span>
                    </span>
                </label>
                <InputError :message="errors.campaign_type" />
            </fieldset>

            <section class="space-y-3 rounded-xl border border-border bg-card p-4">
                <div>
                    <h2 class="text-sm font-medium">Recipient list</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Required. Download the template for your campaign type,
                        fill in contacts, then upload the file here.
                    </p>
                </div>

                <p class="text-sm">
                    <a
                        :href="templateHref"
                        class="font-medium text-primary underline underline-offset-4"
                    >
                        Download
                        {{
                            campaignType === 'personalised_bulk'
                                ? 'personalised bulk'
                                : 'bulk'
                        }}
                        CSV template
                    </a>
                </p>

                <div class="grid gap-2">
                    <Label for="file">Upload CSV or XLSX</Label>
                    <Input
                        id="file"
                        type="file"
                        name="file"
                        accept=".csv,.txt,.xlsx"
                        required
                        @change="onFileChange"
                    />
                    <p
                        v-if="selectedFileName"
                        class="text-xs text-muted-foreground"
                    >
                        Selected: {{ selectedFileName }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Prefer a
                        <code class="rounded bg-muted px-1">phone</code>
                        column. Save Excel columns as Text to avoid number
                        corruption.
                    </p>
                    <InputError :message="errors.file" />
                </div>
            </section>

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
                    :placeholder="
                        campaignType === 'personalised_bulk'
                            ? 'Hello [Name], your code is [CODE].'
                            : 'Write the SMS every recipient will get.'
                    "
                />
                <div
                    class="flex flex-wrap justify-between gap-2 text-xs text-muted-foreground"
                >
                    <span>{{ remaining }} characters left</span>
                    <span v-if="estimateResult">
                        {{ estimateResult.character_count }} chars ·
                        {{ estimateResult.pages }}
                        {{ estimateResult.pages === 1 ? 'page' : 'pages' }}
                    </span>
                </div>
                <p
                    v-if="
                        campaignType === 'personalised_bulk' &&
                        !messageBody.includes('[')
                    "
                    class="text-xs text-muted-foreground"
                >
                    Tip: add placeholders like
                    <code class="rounded bg-muted px-1">[Name]</code>
                    matching columns in your CSV.
                </p>
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
                    <Label for="requested_send_at">Requested send time</Label>
                    <Input
                        id="requested_send_at"
                        name="requested_send_at"
                        type="datetime-local"
                    />
                    <InputError :message="errors.requested_send_at" />
                </div>
                <div class="grid gap-2">
                    <Label for="hard_deadline_at">Hard deadline</Label>
                    <Input
                        id="hard_deadline_at"
                        name="hard_deadline_at"
                        type="datetime-local"
                    />
                    <InputError :message="errors.hard_deadline_at" />
                    <p class="text-xs text-muted-foreground">
                        Optional. Must be on or after the requested send time.
                    </p>
                </div>
            </div>

            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" />
                Save draft &amp; validate list
            </Button>
        </Form>
    </div>
</template>
