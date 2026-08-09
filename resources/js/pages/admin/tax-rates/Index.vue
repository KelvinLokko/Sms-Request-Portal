<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TaxRateController from '@/actions/App/Http/Controllers/Admin/TaxRateController';
import Heading from '@/components/Heading.vue';
import ListPagination from '@/components/ListPagination.vue';
import type { Paginated } from '@/types';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/admin/tax-rates';

type TaxRow = {
    id: number;
    name: string;
    rate: string;
    effective_from: string;
    is_active: boolean;
};

defineProps<{
    taxRates: Paginated<TaxRow>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tax rates', href: index() }],
    },
});
</script>

<template>
    <Head title="Tax rates" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Tax rates"
            description="Configurable tax lines applied at invoice time. Keep inactive until the real levy structure is confirmed."
        />

        <Form
            v-bind="TaxRateController.store.form()"
            class="grid max-w-xl gap-4 rounded-xl border p-4"
            v-slot="{ errors, processing }"
        >
            <h2 class="font-medium">Add tax line</h2>
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" required />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="rate">Rate (%)</Label>
                <Input
                    id="rate"
                    name="rate"
                    type="number"
                    step="0.0001"
                    min="0"
                    max="100"
                    required
                />
                <InputError :message="errors.rate" />
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
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked />
                Active
            </label>
            <Button type="submit" class="w-fit" :disabled="processing">
                <Spinner v-if="processing" />
                Save tax rate
            </Button>
        </Form>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[32rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Rate %</th>
                        <th class="px-4 py-3 font-medium">Effective from</th>
                        <th class="px-4 py-3 font-medium">Active</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="tax in taxRates.data"
                        :key="tax.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">{{ tax.name }}</td>
                        <td class="px-4 py-3 font-mono">{{ tax.rate }}</td>
                        <td class="px-4 py-3">{{ tax.effective_from }}</td>
                        <td class="px-4 py-3">
                            {{ tax.is_active ? 'Yes' : 'No' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="taxRates" />
    </div>
</template>
