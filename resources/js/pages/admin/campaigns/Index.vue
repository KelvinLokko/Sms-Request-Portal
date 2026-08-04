<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CampaignReviewController from '@/actions/App/Http/Controllers/Admin/CampaignReviewController';
import Heading from '@/components/Heading.vue';
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

const props = defineProps<{
    campaigns: { data: Row[] };
    filters: { status: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Campaign review', href: index() }],
    },
});

const statusFilter = ref(props.filters.status);

function applyFilter() {
    router.get(
        index.url(),
        { status: statusFilter.value === 'all' ? undefined : statusFilter.value },
        { preserveState: true },
    );
}
</script>

<template>
    <Head title="Campaign review" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Campaign review"
            description="Review submitted campaigns, request changes, reject blocked content, or issue an invoice."
        />

        <div class="flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-1 text-sm">
                <span class="text-muted-foreground">Status</span>
                <select
                    v-model="statusFilter"
                    class="rounded-md border bg-background px-3 py-2"
                    @change="applyFilter"
                >
                    <option value="all">Submitted + under review</option>
                    <option value="submitted">Submitted</option>
                    <option value="under_review">Under review</option>
                </select>
            </label>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Quote</th>
                        <th class="px-4 py-3 font-medium">Flags</th>
                        <th class="px-4 py-3 font-medium" />
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in campaigns.data"
                        :key="row.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs">{{ row.reference }}</div>
                            <div class="text-muted-foreground">{{ row.name }}</div>
                        </td>
                        <td class="px-4 py-3">{{ row.company.name }}</td>
                        <td class="px-4 py-3">{{ row.status_label }}</td>
                        <td class="px-4 py-3">
                            <div>{{ row.quoted_cost ?? '—' }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ row.billable_recipients?.toLocaleString() ?? '—' }} recipients
                            </div>
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
                                v-if="!row.requires_manual_cost_review && !row.exceeds_621_warning"
                                class="text-muted-foreground"
                            >
                                —
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button as-child size="sm" variant="outline">
                                <Link :href="CampaignReviewController.show.url(row.id)">
                                    Open
                                </Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="campaigns.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No campaigns in the review queue.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
