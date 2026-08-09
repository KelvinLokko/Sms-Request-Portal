<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { EllipsisVertical } from '@lucide/vue';
import { ref } from 'vue';
import SenderIdReviewController from '@/actions/App/Http/Controllers/Admin/SenderIdReviewController';
import Heading from '@/components/Heading.vue';
import ListPagination from '@/components/ListPagination.vue';
import type { Paginated } from '@/types';
import ListFilterBar from '@/components/ListFilterBar.vue';
import type { ListFilterValues } from '@/components/ListFilterBar.vue';
import RejectReasonDialog from '@/components/RejectReasonDialog.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { confirmDialog } from '@/composables/useConfirmDialog';
import { index } from '@/routes/admin/sender-ids';

type Row = {
    id: number;
    value: string;
    status: string;
    status_label: string;
    rejection_reason: string | null;
    company: { id: number; name: string };
    requester: { name: string; email: string };
    reviewer: { name: string } | null;
    has_document: boolean;
    uses_company_letterhead: boolean;
    created_at: string | null;
    reviewed_at: string | null;
    document_url: string | null;
};

defineProps<{
    senderIds: Paginated<Row>;
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
        breadcrumbs: [{ title: 'Sender ID review', href: index() }],
    },
});

const rejectOpen = ref(false);
const rejectTarget = ref<Row | null>(null);

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

async function approve(row: Row) {
    const confirmed = await confirmDialog({
        title: `Approve “${row.value}”?`,
        description: `${row.company.name} will be able to use this sender ID on campaigns.`,
        confirmLabel: 'Approve',
        cancelLabel: 'Cancel',
    });

    if (!confirmed) {
        return;
    }

    router.post(SenderIdReviewController.approve.url(row.id));
}

function openReject(row: Row) {
    rejectTarget.value = row;
    rejectOpen.value = true;
}

function confirmReject(reason: string) {
    if (!rejectTarget.value) {
        return;
    }

    router.post(SenderIdReviewController.reject.url(rejectTarget.value.id), {
        rejection_reason: reason,
    });
    rejectTarget.value = null;
}
</script>

<template>
    <Head title="Sender ID review" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Sender ID review"
            description="Review sender IDs across all statuses before clients use them on campaign requests."
        />

        <ListFilterBar
            :status="filters.status"
            :from="filters.from"
            :to="filters.to"
            :q="filters.q"
            :status-options="statusOptions"
            show-search
            search-placeholder="Search sender ID, company, or requester"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[64rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Sender ID</th>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Document</th>
                        <th class="px-4 py-3 font-medium">Created</th>
                        <th class="px-4 py-3 font-medium">Reviewed by</th>
                        <th class="px-4 py-3 font-medium">Reviewed at</th>
                        <th class="w-14 px-4 py-3 font-medium">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in senderIds.data"
                        :key="row.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3 font-mono">{{ row.value }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">
                                {{ row.company.name }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ row.requester.name }} ·
                                {{ row.requester.email }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ row.status_label }}</div>
                            <p
                                v-if="row.rejection_reason"
                                class="mt-1 max-w-xs text-xs text-muted-foreground"
                            >
                                {{ row.rejection_reason }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <a
                                v-if="row.document_url"
                                :href="row.document_url"
                                class="underline underline-offset-4"
                            >
                                Download
                            </a>
                            <span
                                v-else-if="row.uses_company_letterhead"
                                class="text-muted-foreground"
                            >
                                Letterhead
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(row.created_at) }}
                        </td>
                        <td class="px-4 py-3">
                            {{ row.reviewer?.name ?? '—' }}
                        </td>
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(row.reviewed_at) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <DropdownMenu v-if="row.status === 'pending'">
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        :aria-label="`Actions for ${row.value}`"
                                    >
                                        <EllipsisVertical class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="approve(row)">
                                        Approve
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="openReject(row)"
                                    >
                                        Reject
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                    </tr>
                    <tr v-if="senderIds.data.length === 0">
                        <td
                            colspan="8"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No sender IDs match the current filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="senderIds" />

        <RejectReasonDialog
            v-model:open="rejectOpen"
            :title="
                rejectTarget
                    ? `Reject “${rejectTarget.value}”?`
                    : 'Reject sender ID'
            "
            description="Provide a clear reason. This will be shown to the client."
            confirm-label="Reject sender ID"
            @confirm="confirmReject"
        />
    </div>
</template>
