<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index as analyticsIndex } from '@/routes/admin/analytics';
import { index as campaignsIndex } from '@/routes/campaigns';
import { dashboard } from '@/routes';
import { index as senderIdsIndex } from '@/routes/sender-ids';

type Summary = {
    pending_review: number;
    awaiting_fulfilment: number;
    pending_payments: number;
    fulfilled: number;
    revenue: string;
    sms_volume: number;
};

defineProps<{
    summary: Summary | null;
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
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-8 p-4 sm:p-6">
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
            v-if="!isStaff && company && company.status !== 'approved'"
            class="rounded-2xl border border-amber-400/40 bg-amber-50 p-5 text-sm dark:bg-amber-950/30"
            role="status"
        >
            <p class="font-medium text-amber-950 dark:text-amber-100">
                Company {{ company.status_label.toLowerCase() }}
            </p>
            <p class="mt-1 text-muted-foreground">
                You can register sender IDs while we review your company. Campaign
                requests unlock once an admin approves your account.
            </p>
        </div>

        <div
            v-if="isStaff && summary"
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
        >
            <div class="surface-panel p-4 transition-shadow hover:shadow-md">
                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Pending review
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.pending_review }}
                </p>
            </div>
            <div class="surface-panel p-4 transition-shadow hover:shadow-md">
                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Awaiting fulfilment
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.awaiting_fulfilment }}
                </p>
            </div>
            <div class="surface-panel p-4 transition-shadow hover:shadow-md">
                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Pending payments
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.pending_payments }}
                </p>
            </div>
            <div class="surface-panel p-4 transition-shadow hover:shadow-md">
                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Fulfilled
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.fulfilled }}
                </p>
            </div>
            <div class="surface-panel p-4 transition-shadow hover:shadow-md">
                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Paid revenue
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.revenue }}
                </p>
            </div>
            <div class="surface-panel p-4 transition-shadow hover:shadow-md">
                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    SMS volume
                </p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ summary.sms_volume.toLocaleString() }}
                </p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div
                v-if="!isStaff"
                class="surface-panel flex flex-col gap-3 p-5 transition-shadow hover:shadow-md"
            >
                <h2 class="font-semibold tracking-tight">Sender IDs</h2>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Register the alphanumeric names recipients will see. Each
                    must be approved before use.
                </p>
                <Button as-child class="mt-auto w-fit">
                    <Link :href="senderIdsIndex()">Manage sender IDs</Link>
                </Button>
            </div>

            <div
                v-if="!isStaff && companyApproved"
                class="surface-panel flex flex-col gap-3 p-5 transition-shadow hover:shadow-md"
            >
                <h2 class="font-semibold tracking-tight">Campaigns</h2>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Submit SMS campaign requests, validate recipient lists, and
                    get a quote before you pay.
                </p>
                <Button as-child class="mt-auto w-fit">
                    <Link :href="campaignsIndex()">View campaigns</Link>
                </Button>
            </div>

            <div
                v-if="isStaff"
                class="surface-panel flex flex-col gap-3 p-5 transition-shadow hover:shadow-md"
            >
                <h2 class="font-semibold tracking-tight">Analytics</h2>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Revenue, request volume, SMS volume, and turnaround trends.
                </p>
                <Button as-child class="mt-auto w-fit">
                    <Link :href="analyticsIndex()">Open analytics</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
