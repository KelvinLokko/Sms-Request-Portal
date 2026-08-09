<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import ListPagination from '@/components/ListPagination.vue';
import type { Paginated } from '@/types';
import ListFilterBar from '@/components/ListFilterBar.vue';
import type { ListFilterValues } from '@/components/ListFilterBar.vue';
import { Button } from '@/components/ui/button';
import { index as paymentsIndex, report } from '@/routes/admin/payments';

type Row = {
    id: number;
    status: string;
    status_label: string;
    amount: string;
    momo_reference: string;
    payer_number: string;
    company: { id: number; name: string };
    invoice: {
        id: number;
        number: string;
        total: string;
        campaign_reference: string | null;
    };
    submitter: { name: string; email: string };
    proof_url: string | null;
    created_at: string | null;
    verified_at: string | null;
    verifier: { name: string } | null;
    rejection_reason: string | null;
};

defineProps<{
    payments: Paginated<Row>;
    filters: {
        status: string | null;
        from: string | null;
        to: string | null;
        q: string | null;
    };
    statusOptions: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Payments', href: paymentsIndex() },
            { title: 'History', href: report() },
        ],
    },
});

function applyFilters(values: ListFilterValues) {
    router.get(
        report.url(),
        {
            ...(values.status ? { status: values.status } : {}),
            ...(values.from ? { from: values.from } : {}),
            ...(values.to ? { to: values.to } : {}),
            ...(values.q ? { q: values.q } : {}),
        },
        { preserveState: true, preserveScroll: true },
    );
}

function resetFilters() {
    router.get(report.url(), {}, { preserveState: true, preserveScroll: true });
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head title="Payment history" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Payment history"
                description="Full record of submitted, verified, and rejected Mobile Money payments."
            />
            <Button as-child variant="outline">
                <Link :href="paymentsIndex()">Pending verification</Link>
            </Button>
        </div>

        <ListFilterBar
            :status="filters.status"
            :from="filters.from"
            :to="filters.to"
            :q="filters.q"
            :status-options="statusOptions"
            show-search
            search-placeholder="Search company, invoice, or MoMo ref"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[60rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Submitted</th>
                        <th class="px-4 py-3 font-medium">Invoice</th>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">MoMo</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Reviewed by</th>
                        <th class="px-4 py-3 font-medium">Reviewed at</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in payments.data"
                        :key="row.id"
                        class="border-b last:border-0"
                    >
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(row.created_at) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs">
                                {{ row.invoice.number }}
                            </div>
                            <div class="text-muted-foreground">
                                {{ row.invoice.campaign_reference }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ row.company.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ row.submitter.name }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs">
                                {{ row.momo_reference }}
                            </div>
                            <div>{{ row.payer_number }}</div>
                            <a
                                v-if="row.proof_url"
                                :href="row.proof_url"
                                class="text-xs underline underline-offset-4"
                            >
                                Proof
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ row.amount }}</td>
                        <td class="px-4 py-3">
                            <div>{{ row.status_label }}</div>
                            <div
                                v-if="row.rejection_reason"
                                class="mt-1 max-w-xs text-xs text-muted-foreground"
                            >
                                {{ row.rejection_reason }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            {{ row.verifier?.name ?? '—' }}
                        </td>
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(row.verified_at) }}
                        </td>
                    </tr>
                    <tr v-if="payments.data.length === 0">
                        <td
                            colspan="8"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No payments match the current filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="payments" />
    </div>
</template>
