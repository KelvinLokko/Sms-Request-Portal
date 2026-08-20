<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CampaignReviewController from '@/actions/App/Http/Controllers/Admin/CampaignReviewController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { confirmDialog } from '@/composables/useConfirmDialog';
import { index } from '@/routes/admin/campaigns';

type Campaign = {
    id: number;
    reference: string;
    name: string | null;
    status: string;
    status_label: string;
    message_body: string | null;
    encoding: string;
    pages: number;
    is_personalised: boolean;
    requires_manual_cost_review: boolean;
    exceeds_621_warning: boolean;
    billable_recipients: number | null;
    rate_per_sms: string | null;
    quoted_cost: string | null;
    requested_send_at: string | null;
    hard_deadline_at: string | null;
    submitted_at: string | null;
    company: { id: number; name: string; email: string };
    sender_id: string | null;
    creator: { name: string; email: string };
    recipient_list: {
        billable_count: number;
        valid_count: number;
        invalid_count: number;
        duplicate_count: number;
    } | null;
    invoice_number: string | null;
};

const props = defineProps<{
    campaign: Campaign;
    can: {
        start_review: boolean;
        request_changes: boolean;
        reject: boolean;
        issue_invoice: boolean;
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

const changesReason = ref('');
const rejectionReason = ref('');

function startReview() {
    router.post(CampaignReviewController.startReview.url(props.campaign.id));
}

async function issueInvoice() {
    const confirmed = await confirmDialog({
        title: 'Issue invoice?',
        description:
            'This creates an immutable invoice for the campaign. This cannot be undone.',
        confirmLabel: 'Issue invoice',
        cancelLabel: 'Not yet',
    });

    if (!confirmed) {
        return;
    }

    router.post(CampaignReviewController.issueInvoice.url(props.campaign.id));
}
</script>

<template>
    <Head :title="`Review ${campaign.reference}`" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <Heading
            :title="campaign.reference"
            :description="`${campaign.status_label} · ${campaign.company.name}`"
        />

        <div class="flex flex-wrap gap-2">
            <Button v-if="can.start_review" @click="startReview">
                Start review
            </Button>
            <Button v-if="can.issue_invoice" @click="issueInvoice">
                Issue invoice
            </Button>
        </div>

        <section class="space-y-2 rounded-xl border p-4 text-sm">
            <p>
                <span class="text-muted-foreground">Sender ID:</span>
                {{ campaign.sender_id ?? '—' }}
            </p>
            <p>
                <span class="text-muted-foreground">Encoding:</span>
                {{ campaign.encoding }} · {{ campaign.pages }} page(s)
            </p>
            <p>
                <span class="text-muted-foreground">Quote:</span>
                {{ campaign.quoted_cost ?? '—' }} @
                {{ campaign.rate_per_sms ?? '—' }}/SMS
            </p>
            <p>
                <span class="text-muted-foreground">Billable:</span>
                {{ campaign.billable_recipients?.toLocaleString() ?? '—' }}
            </p>
            <p
                v-if="campaign.requires_manual_cost_review"
                class="text-amber-700 dark:text-amber-400"
            >
                Unicode campaign — verify cost manually before invoicing.
            </p>
            <p
                v-if="campaign.exceeds_621_warning"
                class="text-amber-700 dark:text-amber-400"
            >
                Message exceeds 621 characters.
            </p>
            <p v-if="campaign.invoice_number">
                <span class="text-muted-foreground">Invoice:</span>
                {{ campaign.invoice_number }}
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-sm font-medium">Message</h2>
            <pre
                class="rounded-xl border bg-muted/30 p-4 text-sm whitespace-pre-wrap"
                >{{ campaign.message_body }}</pre>
        </section>

        <section
            v-if="can.request_changes"
            class="space-y-3 rounded-xl border p-4"
        >
            <h2 class="text-sm font-medium">Request changes</h2>
            <Form
                v-bind="
                    CampaignReviewController.requestChanges.form(campaign.id)
                "
                class="space-y-3"
                v-slot="{ errors, processing }"
            >
                <div class="space-y-2">
                    <Label for="reason">Reason</Label>
                    <textarea
                        id="reason"
                        v-model="changesReason"
                        name="reason"
                        class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        required
                    />
                    <InputError :message="errors.reason" />
                </div>
                <Button type="submit" variant="outline" :disabled="processing">
                    <Spinner v-if="processing" />
                    Request changes
                </Button>
            </Form>
        </section>

        <section
            v-if="can.reject"
            class="space-y-3 rounded-xl border border-destructive/30 p-4"
        >
            <h2 class="text-sm font-medium">Reject campaign</h2>
            <Form
                v-bind="CampaignReviewController.reject.form(campaign.id)"
                class="space-y-3"
                v-slot="{ errors, processing }"
            >
                <div class="space-y-2">
                    <Label for="rejection_reason">Rejection reason</Label>
                    <textarea
                        id="rejection_reason"
                        v-model="rejectionReason"
                        name="rejection_reason"
                        class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        required
                        placeholder="e.g. content not permitted — revise wording"
                    />
                    <InputError :message="errors.rejection_reason" />
                </div>
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" />
                    Reject
                </Button>
            </Form>
        </section>
    </div>
</template>
