<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PaymentReviewController from '@/actions/App/Http/Controllers/Admin/PaymentReviewController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index, report } from '@/routes/admin/payments';

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
};

defineProps<{
    payments: { data: Row[] };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Payment verification', href: index() }],
    },
});

const rejectReason = ref<Record<number, string>>({});

function verify(id: number) {
    if (!confirm('Mark this payment as verified?')) {
        return;
    }
    router.post(PaymentReviewController.verify.url(id));
}

function reject(id: number) {
    const reason = rejectReason.value[id]?.trim();
    if (!reason) {
        alert('A rejection reason is required.');
        return;
    }
    router.post(PaymentReviewController.reject.url(id), {
        rejection_reason: reason,
    });
}
</script>

<template>
    <Head title="Payment verification" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Payment verification"
                description="Verify Mobile Money references submitted against open invoices."
            />
            <Button as-child variant="outline">
                <Link :href="report()">Payment history</Link>
            </Button>
        </div>
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[52rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Invoice</th>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">MoMo details</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in payments.data"
                        :key="row.id"
                        class="border-b align-top last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs">{{ row.invoice.number }}</div>
                            <div class="text-muted-foreground">
                                {{ row.invoice.campaign_reference }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ row.company.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ row.submitter.name }} · {{ row.submitter.email }}
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
                            <div class="flex max-w-sm flex-col gap-2">
                                <Button size="sm" @click="verify(row.id)">
                                    Verify
                                </Button>
                                <textarea
                                    v-model="rejectReason[row.id]"
                                    class="min-h-16 w-full rounded-md border bg-background px-2 py-1 text-sm"
                                    placeholder="Rejection reason"
                                />
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="reject(row.id)"
                                >
                                    Reject
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="payments.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No payments awaiting verification.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
