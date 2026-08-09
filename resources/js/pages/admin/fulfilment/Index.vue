<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import FulfilmentController from '@/actions/App/Http/Controllers/Admin/FulfilmentController';
import Heading from '@/components/Heading.vue';
import ListPagination from '@/components/ListPagination.vue';
import type { Paginated } from '@/types';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/admin/fulfilment';

type Row = {
    id: number;
    reference: string;
    name: string | null;
    status: string;
    status_label: string;
    company: { id: number; name: string; slug: string };
    sender_id: string | null;
    billable_recipients: number | null;
    requested_send_at: string | null;
    hard_deadline_at: string | null;
    is_overdue: boolean;
    is_approaching_deadline: boolean;
};

const props = defineProps<{
    campaigns: Paginated<Row>;
    filters: { filter: string };
    overdue_count: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Fulfilment', href: index() }],
    },
});

const filter = ref(props.filters.filter);

function applyFilter() {
    router.get(
        index.url(),
        { filter: filter.value === 'all' ? undefined : filter.value },
        { preserveState: true },
    );
}

function formatWhen(value: string | null): string {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString();
}
</script>

<template>
    <Head title="Fulfilment" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Fulfilment"
            description="Paid campaigns ready to send in Deywuro. Download the cleaned list, copy the message, then mark as sent."
        />

        <div class="flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-1 text-sm">
                <span class="text-muted-foreground">Queue</span>
                <select
                    v-model="filter"
                    class="rounded-md border bg-background px-3 py-2"
                    @change="applyFilter"
                >
                    <option value="all">All ready to send</option>
                    <option value="overdue">
                        Approaching / overdue ({{ overdue_count }})
                    </option>
                </select>
            </label>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">Sender</th>
                        <th class="px-4 py-3 font-medium">Deadline</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium" />
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in campaigns.data"
                        :key="row.id"
                        class="border-b last:border-0"
                        :class="{
                            'bg-red-500/5': row.is_overdue,
                            'bg-amber-500/5':
                                !row.is_overdue && row.is_approaching_deadline,
                        }"
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
                        <td class="px-4 py-3 font-mono">
                            {{ row.sender_id ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ formatWhen(row.hard_deadline_at) }}</div>
                            <div
                                v-if="row.is_overdue"
                                class="text-xs text-red-700 dark:text-red-400"
                            >
                                Overdue
                            </div>
                            <div
                                v-else-if="row.is_approaching_deadline"
                                class="text-xs text-amber-700 dark:text-amber-400"
                            >
                                Due within 24h
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ row.status_label }}</td>
                        <td class="px-4 py-3 text-right">
                            <Button as-child size="sm" variant="outline">
                                <Link
                                    :href="
                                        FulfilmentController.show.url(row.id)
                                    "
                                >
                                    Fulfil
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="campaigns.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No campaigns awaiting fulfilment.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="campaigns" />
    </div>
</template>
