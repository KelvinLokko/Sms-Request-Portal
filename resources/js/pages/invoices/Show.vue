<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/invoices';

type Invoice = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    total: string;
    total_major: string;
    subtotal: string;
    tax: string;
    due_at: string | null;
    issued_at: string | null;
    campaign_reference: string | null;
    pdf_url: string | null;
    items: {
        description: string;
        quantity: number;
        unit_price: string;
        amount: string;
    }[];
    payments: {
        id: number;
        provider: string;
        status: string;
        status_label: string;
        amount: string;
        reference: string | null;
        payer: string | null;
        rejection_reason: string | null;
        created_at: string | null;
    }[];
};

defineProps<{
    invoice: Invoice;
    paystackEnabled: boolean;
    hasPendingPayment: boolean;
    can: { pay: boolean; download: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Invoices', href: index() },
            { title: 'Detail', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="invoice.number" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="invoice.number"
                :description="`${invoice.status_label} · ${invoice.campaign_reference ?? ''}`"
            />
            <div class="flex flex-wrap gap-2">
                <Button v-if="can.download && invoice.pdf_url" as-child>
                    <a :href="invoice.pdf_url">Download PDF</a>
                </Button>
            </div>
        </div>

        <section class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Description</th>
                        <th class="px-4 py-3 font-medium">Qty</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(item, i) in invoice.items"
                        :key="i"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">{{ item.description }}</td>
                        <td class="px-4 py-3">
                            {{ item.quantity.toLocaleString() }}
                        </td>
                        <td class="px-4 py-3">{{ item.amount }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <dl class="grid gap-2 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-muted-foreground">Subtotal</dt>
                <dd>{{ invoice.subtotal }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Tax</dt>
                <dd>{{ invoice.tax }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Total</dt>
                <dd class="font-medium">{{ invoice.total }}</dd>
            </div>
        </dl>

        <section v-if="invoice.payments.length" class="space-y-3">
            <h2 class="text-sm font-medium">Payments</h2>
            <ul class="space-y-2 text-sm">
                <li
                    v-for="payment in invoice.payments"
                    :key="payment.id"
                    class="rounded-xl border p-3"
                >
                    <div class="flex flex-wrap justify-between gap-2">
                        <span>{{ payment.status_label }}</span>
                        <span>{{ payment.amount }}</span>
                    </div>
                    <div class="mt-1 text-muted-foreground">
                        <span class="capitalize">{{ payment.provider }}</span>
                        <template v-if="payment.reference">
                            · Ref {{ payment.reference }}
                        </template>
                        <template v-if="payment.payer">
                            · {{ payment.payer }}
                        </template>
                    </div>
                    <p
                        v-if="payment.rejection_reason"
                        class="mt-2 text-destructive"
                    >
                        {{ payment.rejection_reason }}
                    </p>
                </li>
            </ul>
        </section>

        <section v-if="can.pay" class="space-y-4 rounded-xl border p-4">
            <h2 class="text-sm font-medium">Pay invoice</h2>
            <p class="text-sm text-muted-foreground">
                Pay {{ invoice.total }} securely with Paystack (card or mobile
                money). You will be redirected to complete checkout, then
                returned here.
            </p>

            <p
                v-if="!paystackEnabled"
                class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm text-amber-900 dark:text-amber-100"
            >
                Online payment is not configured yet. Contact support.
            </p>

            <Form
                v-else
                v-bind="InvoiceController.startPaystack.form(invoice.id)"
                class="space-y-3"
                v-slot="{ errors, processing }"
            >
                <InputError
                    :message="
                        errors.payment || errors.invoice || errors.reference
                    "
                />
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    {{
                        hasPendingPayment
                            ? 'Continue to Paystack'
                            : 'Pay with Paystack'
                    }}
                </Button>
            </Form>
        </section>
    </div>
</template>
