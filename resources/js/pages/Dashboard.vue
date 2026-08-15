<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index as analyticsIndex } from '@/routes/admin/analytics';
import { index as adminCampaignsIndex } from '@/routes/admin/campaigns';
import { index as fulfilmentIndex } from '@/routes/admin/fulfilment';
import { index as paymentsIndex } from '@/routes/admin/payments';
import {
    create as campaignsCreate,
    index as campaignsIndex,
    show as campaignsShow,
} from '@/routes/campaigns';
import { dashboard } from '@/routes';
import {
    index as invoicesIndex,
    show as invoicesShow,
} from '@/routes/invoices';
import { index as senderIdsIndex } from '@/routes/sender-ids';

type Summary = {
    pending_review: number;
    awaiting_fulfilment: number;
    pending_payments: number;
    fulfilled: number;
    revenue: string;
    billed_sms: string;
    provider_cost: string;
    profit: string;
    is_loss: boolean;
    margin_percent: number | null;
    provider_rate: string | null;
    sms_volume: number;
};

type CompanyOverview = {
    stats: {
        drafts: number;
        in_flight: number;
        fulfilled: number;
        open_invoices: number;
        open_invoice_total: string;
        approved_sender_ids: number;
        pending_sender_ids: number;
        sms_volume: number;
    };
    recent_campaigns: Array<{
        id: number;
        reference: string;
        name: string | null;
        status: string;
        status_label: string;
        sender_id: string | null;
        billable_recipients: number | null;
        quoted_cost: string | null;
        estimated_cost: string | null;
        created_at: string | null;
    }>;
    recent_invoices: Array<{
        id: number;
        number: string;
        status: string;
        status_label: string;
        total: string;
        campaign_reference: string | null;
        issued_at: string | null;
    }>;
    sender_ids: Array<{
        id: number;
        value: string;
        status: string;
        status_label: string;
    }>;
};

const props = defineProps<{
    summary: Summary | null;
    companyOverview: CompanyOverview | null;
    isStaff: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();
const auth = computed(() => page.props.auth);
const company = computed(() => auth.value.company);
const companyApproved = computed(() => company.value?.status === 'approved');
const stats = computed(() => props.companyOverview?.stats ?? null);

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

const kpiCardClass =
    'surface-panel block p-4 transition-shadow hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-8 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Dashboard"
                :description="
                    isStaff
                        ? 'Platform overview'
                        : company
                          ? `${company.name} — ${company.status_label}`
                          : 'Welcome'
                "
            />

            <div
                v-if="!isStaff && companyApproved"
                class="flex flex-wrap gap-2"
            >
                <Button as-child variant="outline">
                    <Link :href="senderIdsIndex()">Sender IDs</Link>
                </Button>
                <Button as-child variant="outline">
                    <Link :href="invoicesIndex()">Invoices</Link>
                </Button>
                <Button as-child>
                    <Link :href="campaignsCreate()">New campaign</Link>
                </Button>
            </div>
        </div>

        <div
            v-if="!isStaff && company && company.status !== 'approved'"
            class="rounded-2xl border border-amber-400/40 bg-amber-50 p-5 text-sm dark:bg-amber-950/30"
            role="status"
        >
            <p class="font-medium text-amber-950 dark:text-amber-100">
                Company {{ company.status_label.toLowerCase() }}
            </p>
            <p class="mt-1 text-muted-foreground">
                You can register sender IDs while we review your company.
                Campaign requests unlock once an admin approves your account.
            </p>
            <Button as-child class="mt-4" variant="outline">
                <Link :href="senderIdsIndex()">Manage sender IDs</Link>
            </Button>
        </div>

        <!-- Staff KPIs -->
        <div
            v-if="isStaff && summary"
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <Link :href="adminCampaignsIndex()" :class="kpiCardClass">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Pending review
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.pending_review }}
                </p>
            </Link>
            <Link :href="fulfilmentIndex()" :class="kpiCardClass">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Awaiting fulfilment
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.awaiting_fulfilment }}
                </p>
            </Link>
            <Link :href="paymentsIndex()" :class="kpiCardClass">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Pending payments
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.pending_payments }}
                </p>
            </Link>
            <Link
                :href="
                    adminCampaignsIndex({ query: { status: 'fulfilled' } })
                "
                :class="kpiCardClass"
            >
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Fulfilled
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.fulfilled }}
                </p>
            </Link>
            <Link :href="analyticsIndex()" :class="kpiCardClass">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Client SMS billed
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.billed_sms }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Paid invoice subtotals (ex-tax)
                </p>
            </Link>
            <Link :href="analyticsIndex()" :class="kpiCardClass">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Provider cost
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.provider_cost }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Fulfilled campaigns · unit
                    {{
                        summary.provider_rate
                            ? `${summary.provider_rate} GHS`
                            : 'not set'
                    }}
                </p>
            </Link>
            <Link :href="analyticsIndex()" :class="kpiCardClass">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Realized profit
                </p>
                <p
                    class="mt-2 text-2xl font-semibold tracking-tight"
                    :class="
                        summary.is_loss
                            ? 'text-destructive'
                            : 'text-emerald-700 dark:text-emerald-400'
                    "
                >
                    {{ summary.profit }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Fulfilled · billed − provider
                    <span v-if="summary.margin_percent !== null">
                        · {{ summary.margin_percent }}% margin
                    </span>
                </p>
            </Link>
            <Link :href="analyticsIndex()" :class="kpiCardClass">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    SMS volume
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.sms_volume.toLocaleString() }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Paid invoice total {{ summary.revenue }}
                </p>
            </Link>
        </div>

        <div
            v-if="isStaff"
            class="surface-panel flex flex-col gap-3 p-5 transition-shadow hover:shadow-md"
        >
            <h2 class="font-semibold tracking-tight">Analytics</h2>
            <p class="text-sm leading-relaxed text-muted-foreground">
                Revenue, profit, request volume, SMS volume, and turnaround
                trends.
            </p>
            <Button as-child class="mt-auto w-fit">
                <Link :href="analyticsIndex()">Open analytics</Link>
            </Button>
        </div>

        <!-- Company KPIs -->
        <div
            v-if="!isStaff && stats"
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
        >
            <Link :href="campaignsIndex()" :class="kpiCardClass">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    In progress
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ stats.in_flight }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ stats.drafts }} draft{{ stats.drafts === 1 ? '' : 's' }}
                </p>
            </Link>
            <Link
                :href="campaignsIndex({ query: { status: 'fulfilled' } })"
                :class="kpiCardClass"
            >
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Fulfilled
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ stats.fulfilled }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ stats.sms_volume.toLocaleString() }} SMS billed
                </p>
            </Link>
            <Link :href="invoicesIndex()" :class="kpiCardClass">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Open invoices
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ stats.open_invoices }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ stats.open_invoice_total }} outstanding
                </p>
            </Link>
            <Link :href="senderIdsIndex()" :class="kpiCardClass">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Sender IDs
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ stats.approved_sender_ids }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ stats.pending_sender_ids }} pending approval
                </p>
            </Link>
        </div>

        <!-- Company recent activity -->
        <div
            v-if="!isStaff && companyOverview"
            class="grid gap-4 xl:grid-cols-[1.4fr_1fr]"
        >
            <section class="surface-panel overflow-hidden">
                <div
                    class="flex items-center justify-between gap-3 border-b px-5 py-4"
                >
                    <h2 class="font-semibold tracking-tight">
                        Recent campaigns
                    </h2>
                    <Link
                        :href="campaignsIndex()"
                        class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                    >
                        View all
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[28rem] text-left text-sm">
                        <thead class="border-b bg-muted/40">
                            <tr>
                                <th class="px-5 py-3 font-medium">Reference</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 font-medium">Sender</th>
                                <th class="px-5 py-3 font-medium">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in companyOverview.recent_campaigns"
                                :key="row.id"
                                class="border-b last:border-0"
                            >
                                <td class="px-5 py-3">
                                    <Link
                                        :href="campaignsShow(row.id)"
                                        class="font-medium underline-offset-4 hover:underline"
                                    >
                                        {{ row.reference }}
                                    </Link>
                                    <p
                                        v-if="row.name"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ row.name }}
                                    </p>
                                </td>
                                <td class="px-5 py-3">
                                    {{ row.status_label }}
                                </td>
                                <td class="px-5 py-3 font-mono text-xs">
                                    {{ row.sender_id ?? '—' }}
                                </td>
                                <td class="px-5 py-3">
                                    {{
                                        row.quoted_cost ??
                                        row.estimated_cost ??
                                        '—'
                                    }}
                                </td>
                            </tr>
                            <tr
                                v-if="
                                    companyOverview.recent_campaigns.length ===
                                    0
                                "
                            >
                                <td
                                    colspan="4"
                                    class="px-5 py-10 text-center text-muted-foreground"
                                >
                                    No campaigns yet.
                                    <Link
                                        v-if="companyApproved"
                                        :href="campaignsCreate()"
                                        class="ml-1 font-medium text-primary underline-offset-4 hover:underline"
                                    >
                                        Create one
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="grid gap-4">
                <section class="surface-panel overflow-hidden">
                    <div
                        class="flex items-center justify-between gap-3 border-b px-5 py-4"
                    >
                        <h2 class="font-semibold tracking-tight">Invoices</h2>
                        <Link
                            :href="invoicesIndex()"
                            class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                        >
                            View all
                        </Link>
                    </div>
                    <ul class="divide-y">
                        <li
                            v-for="invoice in companyOverview.recent_invoices"
                            :key="invoice.id"
                            class="flex items-start justify-between gap-3 px-5 py-3"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="invoicesShow(invoice.id)"
                                    class="font-medium underline-offset-4 hover:underline"
                                >
                                    {{ invoice.number }}
                                </Link>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        invoice.campaign_reference ??
                                        'Campaign invoice'
                                    }}
                                    · {{ formatDate(invoice.issued_at) }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-medium">
                                    {{ invoice.total }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ invoice.status_label }}
                                </p>
                            </div>
                        </li>
                        <li
                            v-if="companyOverview.recent_invoices.length === 0"
                            class="px-5 py-8 text-center text-sm text-muted-foreground"
                        >
                            No invoices yet.
                        </li>
                    </ul>
                </section>

                <section class="surface-panel overflow-hidden">
                    <div
                        class="flex items-center justify-between gap-3 border-b px-5 py-4"
                    >
                        <h2 class="font-semibold tracking-tight">Sender IDs</h2>
                        <Link
                            :href="senderIdsIndex()"
                            class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                        >
                            Manage
                        </Link>
                    </div>
                    <ul class="divide-y">
                        <li
                            v-for="sender in companyOverview.sender_ids"
                            :key="sender.id"
                            class="flex items-center justify-between gap-3 px-5 py-3"
                        >
                            <span class="font-mono text-sm font-medium">
                                {{ sender.value }}
                            </span>
                            <span class="text-xs text-muted-foreground">
                                {{ sender.status_label }}
                            </span>
                        </li>
                        <li
                            v-if="companyOverview.sender_ids.length === 0"
                            class="px-5 py-8 text-center text-sm text-muted-foreground"
                        >
                            No sender IDs yet.
                            <Link
                                :href="senderIdsIndex()"
                                class="ml-1 font-medium text-primary underline-offset-4 hover:underline"
                            >
                                Register one
                            </Link>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</template>
