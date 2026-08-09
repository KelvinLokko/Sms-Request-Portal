<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { EllipsisVertical } from '@lucide/vue';
import { ref } from 'vue';
import PaymentReviewController from '@/actions/App/Http/Controllers/Admin/PaymentReviewController';
import Heading from '@/components/Heading.vue';
import ListPagination from '@/components/ListPagination.vue';
import type { Paginated } from '@/types';
import RejectReasonDialog from '@/components/RejectReasonDialog.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { confirmDialog } from '@/composables/useConfirmDialog';
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
    payments: Paginated<Row>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Payment verification', href: index() }],
    },
});

const rejectOpen = ref(false);
const rejectTarget = ref<Row | null>(null);

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

async function verify(row: Row) {
    const confirmed = await confirmDialog({
        title: 'Mark payment as verified?',
        description:
            'This will confirm the Mobile Money payment against the invoice.',
        confirmLabel: 'Verify payment',
        cancelLabel: 'Not yet',
    });

    if (!confirmed) {
        return;
    }

    router.post(PaymentReviewController.verify.url(row.id));
}

function openReject(row: Row) {
    rejectTarget.value = row;
    rejectOpen.value = true;
}

function confirmReject(reason: string) {
    if (!rejectTarget.value) {
        return;
    }

    router.post(PaymentReviewController.reject.url(rejectTarget.value.id), {
        rejection_reason: reason,
    });
    rejectTarget.value = null;
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
            <table class="w-full min-w-[56rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Invoice</th>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">MoMo details</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Submitted</th>
                        <th class="w-14 px-4 py-3 font-medium">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in payments.data"
                        :key="row.id"
                        class="border-b last:border-0"
                    >
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
                                {{ row.submitter.name }} ·
                                {{ row.submitter.email }}
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
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(row.created_at) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        :aria-label="`Actions for ${row.invoice.number}`"
                                    >
                                        <EllipsisVertical class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="verify(row)">
                                        Verify
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="openReject(row)"
                                    >
                                        Reject
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </td>
                    </tr>
                    <tr v-if="payments.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No payments awaiting verification.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="payments" />

        <RejectReasonDialog
            v-model:open="rejectOpen"
            :title="
                rejectTarget
                    ? `Reject payment for ${rejectTarget.invoice.number}?`
                    : 'Reject payment'
            "
            description="Provide a clear reason. This will be shown on the invoice."
            confirm-label="Reject payment"
            @confirm="confirmReject"
        />
    </div>
</template>
