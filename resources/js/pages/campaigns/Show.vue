<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CampaignController from '@/actions/App/Http/Controllers/CampaignController';
import Heading from '@/components/Heading.vue';
import SmsPhonePreview from '@/components/SmsPhonePreview.vue';
import { Button } from '@/components/ui/button';
import { confirmDialog } from '@/composables/useConfirmDialog';
import { useRecipientListPolling } from '@/composables/useRecipientListPolling';
import { edit, index } from '@/routes/campaigns';

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

const pageErrors = computed(() => {
    const errors = page.props.errors ?? {};

    return Object.values(errors).flatMap((value) =>
        Array.isArray(value) ? value : [String(value)],
    );
});
const flashSuccess = computed(
    () =>
        (page.props.flash as { success?: string | null } | undefined)?.success,
);
const flashError = computed(
    () => (page.props.flash as { error?: string | null } | undefined)?.error,
);

const listReady = computed(() => {
    const list = props.campaign.recipient_list;

    return (
        list !== null && list.status === 'completed' && list.billable_count > 0
    );
});

const listPending = computed(() => {
    const status = props.campaign.recipient_list?.status;

    return status === 'pending' || status === 'processing';
});

const listStatus = computed(
    () => props.campaign.recipient_list?.status ?? null,
);

useRecipientListPolling(listStatus);

const quoteCost = computed(
    () => props.campaign.quoted_cost ?? props.campaign.estimated_cost ?? '—',
);

const summaryStats = computed(() => [
    { label: 'Page count', value: String(props.campaign.pages) },
    {
        label: 'Recipients',
        value:
            props.campaign.billable_recipients !== null
                ? props.campaign.billable_recipients.toLocaleString()
                : '—',
    },
    { label: 'Rate', value: props.campaign.rate_per_sms ?? '—' },
    { label: 'Estimated cost', value: quoteCost.value },
]);

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

async function submitCampaign() {
    const confirmed = await confirmDialog({
        title: 'Submit for review?',
        description:
            'This campaign will be sent to our team for review. The quote will be frozen.',
        confirmLabel: 'Submit for review',
        cancelLabel: 'Keep drafting',
    });

    if (!confirmed) {
        return;
    }

    router.post(CampaignController.submit.url(props.campaign.id));
}

async function cancelCampaign() {
    const confirmed = await confirmDialog({
        title: 'Cancel this campaign?',
        description:
            'The campaign will be cancelled and can no longer be submitted.',
        confirmLabel: 'Cancel campaign',
        cancelLabel: 'Keep campaign',
        variant: 'destructive',
    });

    if (!confirmed) {
        return;
    }

    router.post(CampaignController.cancel.url(props.campaign.id));
}
</script>

<template>
    <Head :title="campaign.reference" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Heading :title="campaign.name || campaign.reference" />
                <div
                    class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                >
                    <span
                        class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-foreground"
                    >
                        {{ campaign.status_label }}
                    </span>
                    <span v-if="campaign.name" class="font-mono text-xs">
                        {{ campaign.reference }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button v-if="can.update" variant="outline" as-child>
                    <Link :href="edit(campaign.id)">Edit campaign</Link>
                </Button>
                <Button
                    v-if="can.submit"
                    :disabled="!listReady"
                    :title="
                        listReady
                            ? undefined
                            : 'Recipient list must finish validating with at least one billable contact'
                    "
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

        <div
            v-if="flashSuccess"
            class="rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm"
            role="status"
        >
            {{ flashSuccess }}
        </div>

        <div
            v-if="flashError || pageErrors.length"
            class="rounded-lg border border-red-500/20 bg-red-500/5 px-4 py-3 text-sm text-red-800 dark:text-red-200"
            role="alert"
        >
            <p v-if="flashError">{{ flashError }}</p>
            <ul v-if="pageErrors.length" class="list-disc space-y-1 pl-5">
                <li v-for="(error, index) in pageErrors" :key="index">
                    {{ error }}
                </li>
            </ul>
        </div>

        <div
            v-if="campaign.changes_requested_reason"
            class="rounded-lg border border-amber-500/20 bg-amber-500/5 px-4 py-3 text-sm"
        >
            <p class="font-medium">Changes requested</p>
            <p class="mt-1 text-muted-foreground">
                {{ campaign.changes_requested_reason }}
            </p>
        </div>

        <div
            v-if="campaign.rejection_reason"
            class="rounded-lg border border-red-500/20 bg-red-500/5 px-4 py-3 text-sm"
        >
            <p class="font-medium">Rejected</p>
            <p class="mt-1 text-muted-foreground">
                {{ campaign.rejection_reason }}
            </p>
        </div>

        <div
            class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-start"
        >
            <div class="min-w-0 space-y-8">
                <dl
                    class="grid grid-cols-2 gap-x-6 gap-y-4 border-y py-4 text-sm sm:grid-cols-4"
                >
                    <div v-for="stat in summaryStats" :key="stat.label">
                        <dt class="text-muted-foreground">{{ stat.label }}</dt>
                        <dd class="mt-0.5 font-medium tabular-nums">
                            {{ stat.value }}
                        </dd>
                    </div>
                </dl>

                <section class="space-y-3">
                    <h2 class="text-sm font-medium">Message</h2>
                    <p
                        class="text-sm leading-relaxed whitespace-pre-wrap"
                        :class="
                            campaign.message_body ? '' : 'text-muted-foreground'
                        "
                    >
                        {{ campaign.message_body || 'No message yet.' }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Sender
                        <span class="font-medium text-foreground">
                            {{ campaign.sender_id ?? '—' }}
                        </span>
                        ·
                        {{
                            campaign.is_personalised
                                ? 'Personalised bulk'
                                : 'Bulk'
                        }}
                    </p>
                    <p
                        v-if="campaign.exceeds_621_warning"
                        class="text-sm text-amber-700 dark:text-amber-300"
                    >
                        Message exceeds {{ warnThreshold }} characters.
                    </p>
                    <p
                        v-if="campaign.requires_manual_cost_review"
                        class="text-sm text-amber-700 dark:text-amber-300"
                    >
                        Unicode encoding — manual cost review required after
                        submission.
                    </p>
                </section>

                <section class="space-y-3 border-t pt-6">
                    <h2 class="text-sm font-medium">Recipients</h2>
                    <template v-if="campaign.recipient_list">
                        <p class="text-sm">
                            <span class="font-medium">
                                {{ campaign.recipient_list.original_filename }}
                            </span>
                            <span class="text-muted-foreground">
                                · {{ campaign.recipient_list.status_label }}
                            </span>
                        </p>
                        <p
                            v-if="listPending"
                            class="text-sm text-muted-foreground"
                        >
                            Validation is running. This page updates
                            automatically when it finishes.
                        </p>
                        <dl
                            class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4"
                        >
                            <div>
                                <dt class="text-muted-foreground">Valid</dt>
                                <dd class="font-medium tabular-nums">
                                    {{ campaign.recipient_list.valid_count }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Invalid</dt>
                                <dd class="font-medium tabular-nums">
                                    {{ campaign.recipient_list.invalid_count }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">
                                    Duplicates
                                </dt>
                                <dd class="font-medium tabular-nums">
                                    {{
                                        campaign.recipient_list.duplicate_count
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Billable</dt>
                                <dd class="font-medium tabular-nums">
                                    {{ campaign.recipient_list.billable_count }}
                                </dd>
                            </div>
                        </dl>
                        <p
                            v-if="campaign.recipient_list.error_message"
                            class="text-sm text-red-600"
                        >
                            {{ campaign.recipient_list.error_message }}
                        </p>
                        <p
                            v-if="campaign.recipient_list.has_rejected_export"
                            class="text-sm"
                        >
                            <a
                                :href="
                                    CampaignController.downloadRejected.url(
                                        campaign.id,
                                    )
                                "
                                class="underline underline-offset-4"
                            >
                                Download rejected rows
                            </a>
                        </p>
                    </template>
                    <p v-else class="text-sm text-muted-foreground">
                        No recipient list uploaded yet.
                    </p>
                </section>

                <section class="space-y-3 border-t pt-6">
                    <h2 class="text-sm font-medium">Schedule</h2>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">
                                Requested send
                            </dt>
                            <dd class="mt-0.5 font-medium">
                                {{ formatDate(campaign.requested_send_at) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Hard deadline</dt>
                            <dd class="mt-0.5 font-medium">
                                {{ formatDate(campaign.hard_deadline_at) }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section
                    v-if="campaign.invoice"
                    class="space-y-1 border-t pt-6 text-sm"
                >
                    <h2 class="text-sm font-medium">Invoice</h2>
                    <p class="text-muted-foreground">
                        <Link
                            :href="`/invoices/${campaign.invoice.id}`"
                            class="font-medium text-foreground underline-offset-4 hover:underline"
                        >
                            {{ campaign.invoice.number }}
                        </Link>
                        · {{ campaign.invoice.total }}
                    </p>
                </section>
            </div>

            <SmsPhonePreview
                class="lg:sticky lg:top-6"
                :sender="campaign.sender_id"
                :message="campaign.message_body"
                :max-length="maxMessageLength"
            />
        </div>

        <p class="text-sm text-muted-foreground">
            <Link
                :href="index()"
                class="underline-offset-4 hover:text-foreground hover:underline"
            >
                Back to campaigns
            </Link>
        </p>
    </div>
</template>
