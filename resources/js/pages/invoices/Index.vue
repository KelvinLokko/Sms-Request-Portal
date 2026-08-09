<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { EllipsisVertical } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import ListPagination from '@/components/ListPagination.vue';
import type { Paginated } from '@/types';
import ListFilterBar from '@/components/ListFilterBar.vue';
import type { ListFilterValues } from '@/components/ListFilterBar.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { index, show } from '@/routes/invoices';

type Row = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    total: string;
    campaign_reference: string | null;
    campaign_name: string | null;
    issued_at: string | null;
    pdf_url: string;
};

defineProps<{
    invoices: Paginated<Row>;
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
        breadcrumbs: [{ title: 'Invoices', href: index() }],
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
    <Head title="Invoices" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Invoices"
            description="Issued invoices for your SMS campaign requests. Download a PDF any time."
        />

        <ListFilterBar
            :status="filters.status"
            :from="filters.from"
            :to="filters.to"
            :q="filters.q"
            :status-options="statusOptions"
            show-search
            search-placeholder="Search invoice or campaign"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Number</th>
                        <th class="px-4 py-3 font-medium">Campaign</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Issued</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="w-14 px-4 py-3 font-medium">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in invoices.data"
                        :key="row.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3 font-mono text-xs">
                            {{ row.number }}
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ row.campaign_reference }}</div>
                            <div class="text-muted-foreground">
                                {{ row.campaign_name }}
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ row.status_label }}</td>
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(row.issued_at) }}
                        </td>
                        <td class="px-4 py-3 font-medium">{{ row.total }}</td>
                        <td class="px-4 py-3 text-right">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        :aria-label="`Actions for ${row.number}`"
                                    >
                                        <EllipsisVertical class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link :href="show(row.id)">View</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem as-child>
                                        <a :href="row.pdf_url">
                                            Download PDF
                                        </a>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </td>
                    </tr>
                    <tr v-if="invoices.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No invoices yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="invoices" />
    </div>
</template>
