<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import FulfilmentController from '@/actions/App/Http/Controllers/Admin/FulfilmentController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/fulfilment';

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
    sender_id: string | null;
    billable_recipients: number | null;
    quoted_cost: string | null;
    requested_send_at: string | null;
    hard_deadline_at: string | null;
    is_overdue: boolean;
    is_approaching_deadline: boolean;
    portal_campaign_name: string;
    company: { id: number; name: string; slug: string };
    recipient_list: {
        billable_count: number;
        valid_count: number;
    } | null;
    invoice_number: string | null;
    cleaned_recipients_url: string;
};

const props = defineProps<{
    campaign: Campaign;
    can: { fulfil: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Fulfilment', href: index() },
            { title: 'Detail', href: '#' },
        ],
    },
});

const copyStatus = ref('');
const jobReference = ref('');

async function copyText(label: string, value: string) {
    try {
        await navigator.clipboard.writeText(value);
        copyStatus.value = `${label} copied`;
        setTimeout(() => {
            copyStatus.value = '';
        }, 2000);
    } catch {
        copyStatus.value = 'Copy failed — select the text manually';
    }
}

function markFulfilled() {
    if (
        !confirm(
            'Confirm this campaign was sent in Deywuro and mark it fulfilled?',
        )
    ) {
        return;
    }

    router.post(FulfilmentController.markFulfilled.url(props.campaign.id), {
        deywuro_job_reference: jobReference.value || null,
    });
}

function formatWhen(value: string | null): string {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString();
}
</script>

<template>
    <Head :title="`Fulfil ${campaign.reference}`" />

    <div class="mx-auto flex max-w-3xl flex-col gap-6 p-4">
        <Heading
            :title="campaign.reference"
            :description="`${campaign.status_label} · ${campaign.company.name}`"
        />

        <p
            v-if="copyStatus"
            class="text-sm text-muted-foreground"
            role="status"
        >
            {{ copyStatus }}
        </p>

        <section
            class="space-y-3 rounded-xl border p-4 text-sm"
            :class="{
                'border-red-500/40': campaign.is_overdue,
                'border-amber-500/40':
                    !campaign.is_overdue && campaign.is_approaching_deadline,
            }"
        >
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p class="text-muted-foreground">Deywuro campaign name</p>
                    <p class="font-mono text-xs sm:text-sm">
                        {{ campaign.portal_campaign_name }}
                    </p>
                </div>
                <Button
                    size="sm"
                    variant="outline"
                    @click="copyText('Campaign name', campaign.portal_campaign_name)"
                >
                    Copy name
                </Button>
            </div>
            <dl class="grid gap-2 sm:grid-cols-2">
                <div>
                    <dt class="text-muted-foreground">Sender ID</dt>
                    <dd class="font-mono">{{ campaign.sender_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Billable recipients</dt>
                    <dd>{{ campaign.billable_recipients?.toLocaleString() ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Requested send</dt>
                    <dd>{{ formatWhen(campaign.requested_send_at) }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Hard deadline</dt>
                    <dd>
                        {{ formatWhen(campaign.hard_deadline_at) }}
                        <span
                            v-if="campaign.is_overdue"
                            class="ml-1 text-red-700 dark:text-red-400"
                        >
                            (overdue)
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Encoding / pages</dt>
                    <dd>{{ campaign.encoding }} · {{ campaign.pages }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Invoice</dt>
                    <dd>{{ campaign.invoice_number ?? '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="space-y-3">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-medium">Message body</h2>
                <Button
                    size="sm"
                    variant="outline"
                    :disabled="!campaign.message_body"
                    @click="copyText('Message', campaign.message_body ?? '')"
                >
                    Copy message
                </Button>
            </div>
            <pre class="whitespace-pre-wrap rounded-xl border bg-muted/30 p-4 text-sm">{{
                campaign.message_body
            }}</pre>
        </section>

        <section class="flex flex-wrap gap-3">
            <Button as-child>
                <a :href="campaign.cleaned_recipients_url">
                    Download cleaned recipients CSV
                </a>
            </Button>
        </section>

        <section
            v-if="can.fulfil"
            class="space-y-4 rounded-xl border p-4"
        >
            <h2 class="text-sm font-medium">Mark as sent in Deywuro</h2>
            <p class="text-sm text-muted-foreground">
                After you send the campaign in Deywuro, record it here. The optional job
                reference is internal only and never shown to the client.
            </p>
            <div class="space-y-2">
                <Label for="deywuro_job_reference">
                    Deywuro job reference (optional)
                </Label>
                <Input
                    id="deywuro_job_reference"
                    v-model="jobReference"
                    maxlength="255"
                    placeholder="Internal bookkeeping only"
                />
            </div>
            <Button @click="markFulfilled">
                Mark as fulfilled
            </Button>
        </section>
    </div>
</template>
