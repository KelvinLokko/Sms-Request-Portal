<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CampaignController from '@/actions/App/Http/Controllers/CampaignController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/campaigns';

type Campaign = {
    id: number;
    reference: string;
    name: string | null;
    status: string;
    status_label: string;
    message_body: string | null;
    encoding: string;
    flash_type: string;
    is_personalised: boolean;
    pages: number;
    exceeds_621_warning: boolean;
    requires_manual_cost_review: boolean;
    sender_id_id: number | null;
    sender_id: string | null;
    billable_recipients: number | null;
    rate_per_sms: string | null;
    estimated_cost: string | null;
    quoted_cost: string | null;
    requested_send_at: string | null;
    hard_deadline_at: string | null;
    changes_requested_reason: string | null;
    rejection_reason: string | null;
    invoice: {
        id: number;
        number: string;
        status: string;
        total: string;
    } | null;
    recipient_list: {
        status: string;
        status_label: string;
        original_filename: string;
        total_rows: number;
        valid_count: number;
        invalid_count: number;
        duplicate_count: number;
        billable_count: number;
        has_rejected_export: boolean;
        error_message: string | null;
        headers: string[] | null;
    } | null;
};

const props = defineProps<{
    campaign: Campaign;
    senderIds: { id: number; value: string }[];
    maxMessageLength: number;
    warnThreshold: number;
    can: {
        update: boolean;
        submit: boolean;
        cancel: boolean;
        upload: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Campaigns', href: index() },
            { title: 'Detail', href: '#' },
        ],
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

function submitCampaign() {
    if (!confirm('Submit this campaign for review? The quote will be frozen.')) {
        return;
    }
    router.post(CampaignController.submit.url(props.campaign.id));
}

function cancelCampaign() {
    if (!confirm('Cancel this campaign?')) {
        return;
    }
    router.post(CampaignController.cancel.url(props.campaign.id));
}

function toLocalInput(value: string | null): string {
    if (!value) {
        return '';
    }
    const date = new Date(value);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
</script>

<template>
    <Head :title="campaign.reference" />

    <div class="mx-auto flex max-w-3xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="campaign.reference"
                :description="campaign.status_label"
            />
            <div class="flex flex-wrap gap-2">
                <Button
                    v-if="can.submit"
                    @click="submitCampaign"
                >
                    Submit for review
                </Button>
                <Button
                    v-if="can.cancel"
                    variant="outline"
                    @click="cancelCampaign"
                >
                    Cancel
                </Button>
            </div>
        </div>

        <p
            v-if="flashSuccess"
            class="rounded-lg border border-green-500/30 bg-green-500/10 px-3 py-2 text-sm"
            role="status"
        >
            {{ flashSuccess }}
        </p>

        <div
            v-if="campaign.changes_requested_reason"
            class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-sm"
        >
            <p class="font-medium">Changes requested</p>
            <p class="mt-1 text-muted-foreground">
                {{ campaign.changes_requested_reason }}
            </p>
        </div>

        <div
            v-if="campaign.rejection_reason"
            class="rounded-lg border border-red-500/30 bg-red-500/10 p-3 text-sm"
        >
            <p class="font-medium">Rejected</p>
            <p class="mt-1 text-muted-foreground">
                {{ campaign.rejection_reason }}
            </p>
        </div>

        <div
            v-if="campaign.invoice"
            class="rounded-lg border p-3 text-sm"
        >
            <p class="font-medium">Invoice {{ campaign.invoice.number }}</p>
            <p class="mt-1 text-muted-foreground">
                {{ campaign.invoice.total }} ·
                <Link
                    :href="`/invoices/${campaign.invoice.id}`"
                    class="underline underline-offset-4"
                >
                    View invoice
                </Link>
            </p>
        </div>

        <section class="grid gap-3 rounded-xl border p-4 text-sm">
            <h2 class="font-medium">Quote summary</h2>
            <dl class="grid gap-2 sm:grid-cols-2">
                <div>
                    <dt class="text-muted-foreground">Pages / segments</dt>
                    <dd>{{ campaign.pages }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Billable recipients</dt>
                    <dd>{{ campaign.billable_recipients ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Rate (GHS)</dt>
                    <dd class="font-mono">{{ campaign.rate_per_sms ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Estimated / quoted</dt>
                    <dd>
                        {{ campaign.quoted_cost ?? campaign.estimated_cost ?? '—' }}
                    </dd>
                </div>
            </dl>
            <p
                v-if="campaign.exceeds_621_warning"
                class="text-amber-700 dark:text-amber-300"
            >
                Message exceeds {{ warnThreshold }} characters.
            </p>
            <p
                v-if="campaign.requires_manual_cost_review"
                class="text-amber-700 dark:text-amber-300"
            >
                Unicode encoding — manual cost review required after submission.
            </p>
        </section>

        <section
            v-if="can.update"
            class="rounded-xl border p-4"
        >
            <h2 class="mb-4 font-medium">Edit draft</h2>
            <Form
                v-bind="CampaignController.update.form(campaign.id)"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Internal name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="campaign.name ?? ''"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="sender_id_id">Sender ID</Label>
                    <select
                        id="sender_id_id"
                        name="sender_id_id"
                        class="h-9 rounded-md border bg-background px-3 text-sm"
                        :value="campaign.sender_id_id ?? ''"
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
                </div>
                <div class="grid gap-2">
                    <Label for="message_body">Message</Label>
                    <textarea
                        id="message_body"
                        name="message_body"
                        rows="6"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        :maxlength="maxMessageLength"
                        :value="campaign.message_body ?? ''"
                    />
                    <InputError :message="errors.message_body" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="encoding">Encoding</Label>
                        <select
                            id="encoding"
                            name="encoding"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                            :value="campaign.encoding"
                        >
                            <option value="text">Text (GSM)</option>
                            <option value="unicode">Unicode</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="flash_type">Message type</Label>
                        <select
                            id="flash_type"
                            name="flash_type"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                            :value="campaign.flash_type"
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
                        :checked="campaign.is_personalised"
                    />
                    <span>Personalised message</span>
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="requested_send_at">Requested send time</Label>
                        <Input
                            id="requested_send_at"
                            name="requested_send_at"
                            type="datetime-local"
                            :default-value="toLocalInput(campaign.requested_send_at)"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="hard_deadline_at">Hard deadline</Label>
                        <Input
                            id="hard_deadline_at"
                            name="hard_deadline_at"
                            type="datetime-local"
                            :default-value="toLocalInput(campaign.hard_deadline_at)"
                        />
                    </div>
                </div>
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Save changes
                </Button>
            </Form>
        </section>

        <section
            v-else
            class="rounded-xl border p-4 text-sm"
        >
            <h2 class="mb-2 font-medium">Message</h2>
            <p class="whitespace-pre-wrap">{{ campaign.message_body }}</p>
            <p class="mt-3 text-muted-foreground">
                Sender:
                <span class="font-mono text-foreground">{{
                    campaign.sender_id ?? '—'
                }}</span>
            </p>
        </section>

        <section class="rounded-xl border p-4">
            <h2 class="mb-3 font-medium">Recipient list</h2>

            <div
                v-if="campaign.recipient_list"
                class="mb-4 space-y-1 text-sm"
            >
                <p>
                    File:
                    <span class="font-medium">{{
                        campaign.recipient_list.original_filename
                    }}</span>
                    · {{ campaign.recipient_list.status_label }}
                </p>
                <p>
                    {{ campaign.recipient_list.valid_count }} valid /
                    {{ campaign.recipient_list.invalid_count }} invalid /
                    {{ campaign.recipient_list.duplicate_count }} duplicates →
                    <strong>{{ campaign.recipient_list.billable_count }} billable</strong>
                </p>
                <p
                    v-if="campaign.recipient_list.error_message"
                    class="text-red-600"
                >
                    {{ campaign.recipient_list.error_message }}
                </p>
                <p v-if="campaign.recipient_list.has_rejected_export">
                    <a
                        :href="
                            CampaignController.downloadRejected.url(campaign.id)
                        "
                        class="underline underline-offset-4"
                    >
                        Download rejected rows
                    </a>
                </p>
            </div>
            <p
                v-else
                class="mb-4 text-sm text-muted-foreground"
            >
                No recipient list uploaded yet.
            </p>

            <Form
                v-if="can.upload"
                v-bind="CampaignController.uploadRecipients.form(campaign.id)"
                enctype="multipart/form-data"
                class="space-y-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="file">Upload CSV or XLSX</Label>
                    <Input
                        id="file"
                        type="file"
                        name="file"
                        accept=".csv,.txt,.xlsx"
                        required
                    />
                    <p class="text-xs text-muted-foreground">
                        Include a header row. Prefer a
                        <code class="rounded bg-muted px-1">phone</code>
                        column. Save Excel columns as Text to avoid number
                        corruption.
                    </p>
                    <InputError :message="errors.file" />
                </div>
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Upload &amp; validate
                </Button>
            </Form>
        </section>

        <p class="text-sm">
            <Link :href="index()" class="underline underline-offset-4"
                >Back to campaigns</Link
            >
        </p>
    </div>
</template>
