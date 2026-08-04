<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        status: string;
        status_label: string;
        amount: string;
        momo_reference: string;
        payer_number: string;
        rejection_reason: string | null;
        created_at: string | null;
    }[];
};

const props = defineProps<{
    invoice: Invoice;
    can: { pay: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Invoices', href: index() },
            { title: 'Detail', href: '#' },
        ],
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
</script>

<template>
    <Head :title="invoice.number" />

    <div class="mx-auto flex max-w-3xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="invoice.number"
                :description="`${invoice.status_label} · ${invoice.campaign_reference ?? ''}`"
            />
            <Button
                v-if="invoice.pdf_url"
                as-child
                variant="outline"
            >
                <a :href="invoice.pdf_url">Download PDF</a>
            </Button>
        </div>

        <p
            v-if="flashSuccess"
            class="rounded-lg border border-green-500/30 bg-green-500/10 px-3 py-2 text-sm"
            role="status"
        >
            {{ flashSuccess }}
        </p>

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
                        <td class="px-4 py-3">{{ item.quantity.toLocaleString() }}</td>
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
                        Ref {{ payment.momo_reference }} · {{ payment.payer_number }}
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

        <section
            v-if="can.pay"
            class="space-y-4 rounded-xl border p-4"
        >
            <h2 class="text-sm font-medium">Submit Mobile Money payment</h2>
            <p class="text-sm text-muted-foreground">
                Pay {{ invoice.total }} offline, then enter the MoMo reference here for finance to verify.
            </p>
            <Form
                v-bind="InvoiceController.storePayment.form(invoice.id)"
                enctype="multipart/form-data"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="space-y-2">
                    <Label for="amount">Amount (GHS)</Label>
                    <Input
                        id="amount"
                        name="amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        :default-value="invoice.total_major"
                        required
                    />
                    <InputError :message="errors.amount" />
                </div>
                <div class="space-y-2">
                    <Label for="momo_reference">MoMo reference</Label>
                    <Input
                        id="momo_reference"
                        name="momo_reference"
                        required
                        maxlength="100"
                    />
                    <InputError :message="errors.momo_reference" />
                </div>
                <div class="space-y-2">
                    <Label for="payer_number">Payer number</Label>
                    <Input
                        id="payer_number"
                        name="payer_number"
                        required
                        maxlength="32"
                        placeholder="23324…"
                    />
                    <InputError :message="errors.payer_number" />
                </div>
                <div class="space-y-2">
                    <Label for="proof">Proof (optional)</Label>
                    <Input
                        id="proof"
                        name="proof"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                    />
                    <InputError :message="errors.proof" />
                </div>
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Submit payment
                </Button>
            </Form>
        </section>
    </div>
</template>
