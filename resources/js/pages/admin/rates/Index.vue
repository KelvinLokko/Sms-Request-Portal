<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import CompanyRateController from '@/actions/App/Http/Controllers/Admin/CompanyRateController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import ListPagination from '@/components/ListPagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/admin/rates';
import type { Paginated } from '@/types';

type RateRow = {
    id: number;
    company: { id: number; name: string } | null;
    rate_per_sms: string;
    effective_from: string;
    is_platform_default: boolean;
};

type ProviderRateRow = {
    id: number;
    rate_per_sms: string;
    effective_from: string;
};

defineProps<{
    rates: Paginated<RateRow>;
    providerRates: Paginated<ProviderRateRow>;
    currentProviderRate: string | null;
    currentClientRate: string | null;
    companies: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'SMS rates', href: index() }],
    },
});

const today = new Date().toISOString().slice(0, 10);
</script>

<template>
    <Head title="SMS rates" />

    <div
        class="mx-auto flex w-full max-w-6xl flex-col gap-10 p-4 sm:p-6 lg:p-8"
    >
        <Heading
            title="SMS rates"
            description="Set what clients pay and what you pay the SMS gateway. Snapshots keep historical profit accurate."
        />

        <!-- Overview strip -->
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border bg-card p-5 shadow-xs">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Provider cost (you pay)
                </p>
                <p class="mt-2 font-mono text-2xl font-semibold tracking-tight">
                    <template v-if="currentProviderRate">
                        {{ currentProviderRate }}
                        <span
                            class="text-base font-normal text-muted-foreground"
                            >GHS / page</span
                        >
                    </template>
                    <span v-else class="text-destructive">Not set</span>
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    Used to calculate provider cost and realized profit.
                </p>
            </div>
            <div class="rounded-xl border bg-card p-5 shadow-xs">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Client rate (they pay)
                </p>
                <p class="mt-2 font-mono text-2xl font-semibold tracking-tight">
                    <template v-if="currentClientRate">
                        {{ currentClientRate }}
                        <span
                            class="text-base font-normal text-muted-foreground"
                            >GHS / page</span
                        >
                    </template>
                    <span v-else class="text-muted-foreground">—</span>
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    Platform default. Companies can have overrides below.
                </p>
            </div>
        </div>

        <!-- Provider -->
        <section class="space-y-5">
            <div class="border-b pb-3">
                <h2 class="text-lg font-semibold tracking-tight">
                    Third-party provider
                </h2>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Unit price you pay the SMS gateway per page. Required before
                    invoicing or marking a campaign fulfilled.
                </p>
            </div>

            <div
                class="grid gap-6 lg:grid-cols-[minmax(0,22rem)_1fr] lg:items-start"
            >
                <Form
                    v-bind="CompanyRateController.storeProvider.form()"
                    class="flex flex-col gap-4 rounded-xl border bg-card p-5 shadow-xs"
                    v-slot="{ errors, processing }"
                >
                    <div>
                        <h3 class="text-sm font-medium">Add provider price</h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Append-only — new rows supersede older ones by
                            effective date.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="provider_rate_per_sms"
                            >Unit price (GHS)</Label
                        >
                        <Input
                            id="provider_rate_per_sms"
                            name="rate_per_sms"
                            type="number"
                            step="0.000001"
                            min="0"
                            required
                            placeholder="0.020000"
                        />
                        <InputError :message="errors.rate_per_sms" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="provider_effective_from"
                            >Effective from</Label
                        >
                        <Input
                            id="provider_effective_from"
                            name="effective_from"
                            type="date"
                            required
                            :default-value="today"
                        />
                        <InputError :message="errors.effective_from" />
                    </div>

                    <Button type="submit" class="w-full" :disabled="processing">
                        <Spinner v-if="processing" />
                        Save provider price
                    </Button>
                </Form>

                <div class="min-w-0 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-medium">Price history</h3>
                        <span class="text-xs text-muted-foreground">
                            {{ providerRates.total.toLocaleString() }}
                            {{
                                providerRates.total === 1 ? 'entry' : 'entries'
                            }}
                        </span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border bg-card">
                        <table class="w-full min-w-[28rem] text-left text-sm">
                            <thead class="border-b bg-muted/40">
                                <tr>
                                    <th class="px-4 py-3 font-medium">
                                        Unit price
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Effective from
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(rate, i) in providerRates.data"
                                    :key="rate.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="px-4 py-3 font-mono">
                                        {{ rate.rate_per_sms }}
                                        <span class="text-muted-foreground"
                                            >GHS</span
                                        >
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                                    >
                                        {{ rate.effective_from }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="
                                                i === 0 && currentProviderRate
                                            "
                                            class="inline-flex rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300"
                                        >
                                            Current
                                        </span>
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                            >—</span
                                        >
                                    </td>
                                </tr>
                                <tr v-if="providerRates.data.length === 0">
                                    <td
                                        colspan="3"
                                        class="px-4 py-12 text-center text-muted-foreground"
                                    >
                                        No provider price yet. Add one to unlock
                                        invoicing and profit tracking.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <ListPagination :paginator="providerRates" />
                </div>
            </div>
        </section>

        <!-- Client rates -->
        <section class="space-y-5">
            <div class="border-b pb-3">
                <h2 class="text-lg font-semibold tracking-tight">
                    Client billing
                </h2>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Platform default and optional per-company overrides.
                    Campaign quotes freeze the client rate at submit.
                </p>
            </div>

            <div
                class="grid gap-6 lg:grid-cols-[minmax(0,22rem)_1fr] lg:items-start"
            >
                <Form
                    v-bind="CompanyRateController.store.form()"
                    class="flex flex-col gap-4 rounded-xl border bg-card p-5 shadow-xs"
                    v-slot="{ errors, processing }"
                >
                    <div>
                        <h3 class="text-sm font-medium">Add client rate</h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Leave company blank for the platform default.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="company_id">Company</Label>
                        <select
                            id="company_id"
                            name="company_id"
                            class="h-9 w-full rounded-md border bg-background px-3 text-sm"
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
                            :default-value="today"
                        />
                        <InputError :message="errors.effective_from" />
                    </div>

                    <Button type="submit" class="w-full" :disabled="processing">
                        <Spinner v-if="processing" />
                        Save client rate
                    </Button>
                </Form>

                <div class="min-w-0 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-medium">Rate history</h3>
                        <span class="text-xs text-muted-foreground">
                            {{ rates.total.toLocaleString() }}
                            {{ rates.total === 1 ? 'entry' : 'entries' }}
                        </span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border bg-card">
                        <table class="w-full min-w-[36rem] text-left text-sm">
                            <thead class="border-b bg-muted/40">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Scope</th>
                                    <th class="px-4 py-3 font-medium">
                                        Rate (GHS)
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Effective from
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="rate in rates.data"
                                    :key="rate.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="rate.is_platform_default"
                                            class="inline-flex rounded-md border bg-muted/60 px-2 py-0.5 text-xs font-medium"
                                        >
                                            Platform default
                                        </span>
                                        <span v-else class="font-medium">{{
                                            rate.company?.name
                                        }}</span>
                                    </td>
                                    <td class="px-4 py-3 font-mono">
                                        {{ rate.rate_per_sms }}
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                                    >
                                        {{ rate.effective_from }}
                                    </td>
                                </tr>
                                <tr v-if="rates.data.length === 0">
                                    <td
                                        colspan="3"
                                        class="px-4 py-12 text-center text-muted-foreground"
                                    >
                                        No client rates yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <ListPagination :paginator="rates" />
                </div>
            </div>
        </section>
    </div>
</template>
