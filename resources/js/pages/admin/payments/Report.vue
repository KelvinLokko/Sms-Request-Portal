<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

const props = defineProps<{
    payments: { data: Row[] };
    filters: { status: string | null; q: string | null };
    statuses: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Payments', href: paymentsIndex() },
            { title: 'History', href: report() },
        ],
    },
});

function filterStatus(status: string | null) {
    router.get(
        report.url(),
        {
            ...(status ? { status } : {}),
            ...(props.filters.q ? { q: props.filters.q } : {}),
        },
        { preserveState: true },
    );
}

function search(event: Event) {
    const value = (event.target as HTMLInputElement).value.trim();
    router.get(
        report.url(),
        {
            ...(props.filters.status ? { status: props.filters.status } : {}),
            ...(value ? { q: value } : {}),
        },
        { preserveState: true, replace: true },
    );
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString();
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

        <div class="flex flex-wrap items-center gap-2">
            <Button
                size="sm"
                :variant="!filters.status ? 'default' : 'outline'"
                @click="filterStatus(null)"
            >
                All
            </Button>
            <Button
                v-for="status in statuses"
                :key="status.value"
                size="sm"
                :variant="filters.status === status.value ? 'default' : 'outline'"
                @click="filterStatus(status.value)"
            >
                {{ status.label }}
            </Button>
            <Input
                class="max-w-xs"
                type="search"
                placeholder="Search company, invoice, MoMo ref"
                :default-value="filters.q ?? ''"
                @change="search"
            />
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[56rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">When</th>
                        <th class="px-4 py-3 font-medium">Invoice</th>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">MoMo</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in payments.data"
                        :key="row.id"
                        class="border-b align-top last:border-0"
                    >
                        <td class="px-4 py-3 text-xs text-muted-foreground">
                            <div>{{ formatDate(row.created_at) }}</div>
                            <div v-if="row.verified_at">
                                Verified {{ formatDate(row.verified_at) }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs">{{ row.invoice.number }}</div>
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
                            <div class="font-mono text-xs">{{ row.momo_reference }}</div>
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
                                v-if="row.verifier"
                                class="text-xs text-muted-foreground"
                            >
                                by {{ row.verifier.name }}
                            </div>
                            <div
                                v-if="row.rejection_reason"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ row.rejection_reason }}
                            </div>
                        </td>
                    </tr>
                    <tr v-if="payments.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No payments match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
