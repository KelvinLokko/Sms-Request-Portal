<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import CampaignReviewController from '@/actions/App/Http/Controllers/Admin/CampaignReviewController';
import Heading from '@/components/Heading.vue';
import ListPagination from '@/components/ListPagination.vue';
import type { Paginated } from '@/types';
import ListFilterBar from '@/components/ListFilterBar.vue';
import type { ListFilterValues } from '@/components/ListFilterBar.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/admin/campaigns';

type Row = {
    id: number;
    reference: string;
    name: string | null;
    status: string;
    status_label: string;
    company: { id: number; name: string };
    sender_id: string | null;
    billable_recipients: number | null;
    quoted_cost: string | null;
    requires_manual_cost_review: boolean;
    exceeds_621_warning: boolean;
    submitted_at: string | null;
};

defineProps<{
    campaigns: Paginated<Row>;
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
        breadcrumbs: [{ title: 'Campaign review', href: index() }],
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
    <Head title="Campaign review" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Campaign review"
            description="Review submitted campaigns, request changes, reject blocked content, or issue an invoice."
        />

        <ListFilterBar
            :status="filters.status"
            :from="filters.from"
            :to="filters.to"
            :q="filters.q"
            :status-options="statusOptions"
            show-search
            search-placeholder="Search reference, name, or company"
            from-label="Submitted from"
            to-label="Submitted to"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[56rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Quote</th>
                        <th class="px-4 py-3 font-medium">Submitted</th>
                        <th class="px-4 py-3 font-medium">Flags</th>
                        <th class="w-24 px-4 py-3 font-medium">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in campaigns.data"
                        :key="row.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs">
                                {{ row.reference }}
                            </div>
                            <div class="text-muted-foreground">
                                {{ row.name }}
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ row.company.name }}</td>
                        <td class="px-4 py-3">{{ row.status_label }}</td>
                        <td class="px-4 py-3">
                            <div>{{ row.quoted_cost ?? '—' }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{
                                    row.billable_recipients?.toLocaleString() ??
                                    '—'
                                }}
                                recipients
                            </div>
                        </td>
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(row.submitted_at) }}
                        </td>
                        <td class="px-4 py-3 text-xs">
                            <span
                                v-if="row.requires_manual_cost_review"
                                class="mr-2 text-amber-700 dark:text-amber-400"
                            >
                                Unicode review
                            </span>
                            <span
                                v-if="row.exceeds_621_warning"
                                class="text-amber-700 dark:text-amber-400"
                            >
                                >621 chars
                            </span>
                            <span
                                v-if="
                                    !row.requires_manual_cost_review &&
                                    !row.exceeds_621_warning
                                "
                                class="text-muted-foreground"
                            >
                                —
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button as-child size="sm" variant="outline">
                                <Link
                                    :href="
                                        CampaignReviewController.show.url(
                                            row.id,
                                        )
                                    "
                                >
                                    Open
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="campaigns.data.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No campaigns match the current filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="campaigns" />
    </div>
</template>
