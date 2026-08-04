<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CompanyRateController from '@/actions/App/Http/Controllers/Admin/CompanyRateController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/admin/rates';

type RateRow = {
    id: number;
    company: { id: number; name: string } | null;
    rate_per_sms: string;
    effective_from: string;
    is_platform_default: boolean;
};

defineProps<{
    rates: { data: RateRow[] };
    companies: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'SMS rates', href: index() }],
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
</script>

<template>
    <Head title="SMS rates" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="SMS rates"
            description="Platform default and per-company overrides. Historical invoices use the rate effective on the invoice date."
        />

        <p
            v-if="flashSuccess"
            class="rounded-lg border border-green-500/30 bg-green-500/10 px-3 py-2 text-sm"
            role="status"
        >
            {{ flashSuccess }}
        </p>

        <Form
            v-bind="CompanyRateController.store.form()"
            class="grid max-w-xl gap-4 rounded-xl border p-4"
            v-slot="{ errors, processing }"
        >
            <h2 class="font-medium">Add rate</h2>
            <div class="grid gap-2">
                <Label for="company_id">Company (blank = platform default)</Label>
                <select
                    id="company_id"
                    name="company_id"
                    class="h-9 rounded-md border bg-background px-3 text-sm"
                >
                    <option value="">Platform default</option>
                    <option
                        v-for="company in companies"
                        :key="company.id"
                        :value="company.id"
                    >
                        {{ company.name }}
                    </option>
                </select>
                <InputError :message="errors.company_id" />
            </div>
            <div class="grid gap-2">
                <Label for="rate_per_sms">Rate per SMS (GHS)</Label>
                <Input
                    id="rate_per_sms"
                    name="rate_per_sms"
                    type="number"
                    step="0.000001"
                    min="0"
                    required
                    placeholder="0.030000"
                />
                <InputError :message="errors.rate_per_sms" />
            </div>
            <div class="grid gap-2">
                <Label for="effective_from">Effective from</Label>
                <Input
                    id="effective_from"
                    name="effective_from"
                    type="date"
                    required
                />
                <InputError :message="errors.effective_from" />
            </div>
            <Button type="submit" class="w-fit" :disabled="processing">
                <Spinner v-if="processing" />
                Save rate
            </Button>
        </Form>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[32rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Scope</th>
                        <th class="px-4 py-3 font-medium">Rate (GHS)</th>
                        <th class="px-4 py-3 font-medium">Effective from</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="rate in rates.data"
                        :key="rate.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            {{
                                rate.is_platform_default
                                    ? 'Platform default'
                                    : rate.company?.name
                            }}
                        </td>
                        <td class="px-4 py-3 font-mono">{{ rate.rate_per_sms }}</td>
                        <td class="px-4 py-3">{{ rate.effective_from }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
