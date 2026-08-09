<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import ListPagination from '@/components/ListPagination.vue';
import type { Paginated } from '@/types';
import ListFilterBar from '@/components/ListFilterBar.vue';
import type { ListFilterValues } from '@/components/ListFilterBar.vue';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/campaigns';

type CampaignRow = {
    id: number;
    reference: string;
    name: string | null;
    status: string;
    status_label: string;
    sender_id: string | null;
    billable_recipients: number | null;
    estimated_cost: string | null;
    quoted_cost: string | null;
    created_at: string | null;
};

defineProps<{
    campaigns: Paginated<CampaignRow>;
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
        breadcrumbs: [{ title: 'Campaigns', href: index() }],
    },
});

function applyFilters(values: ListFilterValues) {
    router.get(
        index.url(),
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
    router.get(index.url(), {}, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <Head title="Campaigns" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Campaign requests"
                description="Draft, validate recipient lists, and submit campaigns for our team to send."
            />
            <Button as-child>
                <Link :href="create()">New campaign</Link>
            </Button>
        </div>

        <ListFilterBar
            :status="filters.status"
            :from="filters.from"
            :to="filters.to"
            :q="filters.q"
            :status-options="statusOptions"
            show-search
            search-placeholder="Search reference, name, or sender"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Sender</th>
                        <th class="px-4 py-3 font-medium">Recipients</th>
                        <th class="px-4 py-3 font-medium">Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in campaigns.data"
                        :key="row.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="show(row.id)"
                                class="font-medium underline-offset-4 hover:underline"
                            >
                                {{ row.reference }}
                            </Link>
                            <p
                                v-if="row.name"
                                class="text-xs text-muted-foreground"
                            >
                                {{ row.name }}
                            </p>
                        </td>
                        <td class="px-4 py-3">{{ row.status_label }}</td>
                        <td class="px-4 py-3 font-mono">
                            {{ row.sender_id ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ row.billable_recipients ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ row.quoted_cost ?? row.estimated_cost ?? '—' }}
                        </td>
                    </tr>
                    <tr v-if="campaigns.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No campaigns yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="campaigns" />
    </div>
</template>
