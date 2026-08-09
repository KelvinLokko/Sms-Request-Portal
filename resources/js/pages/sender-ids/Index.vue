<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import ListPagination from '@/components/ListPagination.vue';
import type { Paginated } from '@/types';
import ListFilterBar from '@/components/ListFilterBar.vue';
import type { ListFilterValues } from '@/components/ListFilterBar.vue';
import { Button } from '@/components/ui/button';
import { confirmDialog } from '@/composables/useConfirmDialog';
import { create, destroy, edit, index } from '@/routes/sender-ids';

type SenderIdRow = {
    id: number;
    value: string;
    status: string;
    status_label: string;
    has_document: boolean;
    rejection_reason: string | null;
    created_at: string | null;
    can_edit: boolean;
    can_delete: boolean;
    document_url: string | null;
};

defineProps<{
    senderIds: Paginated<SenderIdRow>;
    companyStatus: string | null;
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
        breadcrumbs: [{ title: 'Sender IDs', href: index() }],
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

async function deleteSenderId(id: number) {
    const confirmed = await confirmDialog({
        title: 'Delete this sender ID?',
        description: 'This cannot be undone.',
        confirmLabel: 'Delete',
        cancelLabel: 'Keep sender ID',
        variant: 'destructive',
    });

    if (!confirmed) {
        return;
    }

    router.delete(destroy.url(id));
}
</script>

<template>
    <Head title="Sender IDs" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Sender IDs"
                description="Register names recipients will see (max 11 characters). Approved and rejected sender IDs are locked."
            />
            <Button as-child>
                <Link :href="create()">Register sender ID</Link>
            </Button>
        </div>

        <ListFilterBar
            :status="filters.status"
            :from="filters.from"
            :to="filters.to"
            :q="filters.q"
            :status-options="statusOptions"
            show-search
            search-placeholder="Search sender ID"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[32rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Sender ID</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Document</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
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
                            <span>{{ row.status_label }}</span>
                            <p
                                v-if="row.rejection_reason"
                                class="mt-1 text-xs text-muted-foreground"
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
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <div
                                v-if="row.can_edit || row.can_delete"
                                class="flex gap-2"
                            >
                                <Button
                                    v-if="row.can_edit"
                                    variant="outline"
                                    size="sm"
                                    as-child
                                >
                                    <Link :href="edit(row.id)">Edit</Link>
                                </Button>
                                <Button
                                    v-if="row.can_delete"
                                    variant="ghost"
                                    size="sm"
                                    @click="deleteSenderId(row.id)"
                                >
                                    Delete
                                </Button>
                            </div>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                    </tr>
                    <tr v-if="senderIds.data.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No sender IDs yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="senderIds" />
    </div>
</template>
