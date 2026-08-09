<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { EllipsisVertical } from '@lucide/vue';
import { ref } from 'vue';
import CompanyController from '@/actions/App/Http/Controllers/Admin/CompanyController';
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
import { index } from '@/routes/admin/companies';

type CompanyRow = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: string;
    status_label: string;
    users_count: number;
    rejection_reason: string | null;
    created_at: string | null;
    approved_at: string | null;
    approver: { name: string } | null;
};

defineProps<{
    companies: Paginated<CompanyRow>;
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
        breadcrumbs: [{ title: 'Companies', href: index() }],
    },
});

const rejectOpen = ref(false);
const rejectTarget = ref<CompanyRow | null>(null);

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

async function approve(company: CompanyRow) {
    const confirmed = await confirmDialog({
        title: `Approve ${company.name}?`,
        description:
            'The company will be able to submit campaigns once approved.',
        confirmLabel: 'Approve',
        cancelLabel: 'Cancel',
    });

    if (!confirmed) {
        return;
    }

    router.post(CompanyController.approve.url(company.id));
}

function openReject(company: CompanyRow) {
    rejectTarget.value = company;
    rejectOpen.value = true;
}

function confirmReject(reason: string) {
    if (!rejectTarget.value) {
        return;
    }

    router.post(CompanyController.reject.url(rejectTarget.value.id), {
        rejection_reason: reason,
    });
    rejectTarget.value = null;
}

async function suspend(company: CompanyRow) {
    const confirmed = await confirmDialog({
        title: `Suspend ${company.name}?`,
        description:
            'The company will lose access to submit campaigns until reinstated.',
        confirmLabel: 'Suspend',
        cancelLabel: 'Keep active',
        variant: 'destructive',
    });

    if (!confirmed) {
        return;
    }

    router.post(CompanyController.suspend.url(company.id));
}
</script>

<template>
    <Head title="Companies" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Companies"
            description="Approve or reject company registrations before they can submit campaigns."
        />

        <ListFilterBar
            :status="filters.status"
            :from="filters.from"
            :to="filters.to"
            :q="filters.q"
            :status-options="statusOptions"
            show-search
            search-placeholder="Search company, email, or phone"
            @apply="applyFilters"
            @reset="resetFilters"
        />

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[56rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Users</th>
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
                        v-for="company in companies.data"
                        :key="company.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ company.name }}</div>
                            <div class="text-muted-foreground">
                                {{ company.email }}
                            </div>
                            <div
                                v-if="company.phone"
                                class="text-xs text-muted-foreground"
                            >
                                {{ company.phone }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ company.status_label }}</div>
                            <p
                                v-if="company.rejection_reason"
                                class="mt-1 max-w-xs text-xs text-muted-foreground"
                            >
                                {{ company.rejection_reason }}
                            </p>
                        </td>
                        <td class="px-4 py-3 tabular-nums">
                            {{ company.users_count }}
                        </td>
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(company.created_at) }}
                        </td>
                        <td class="px-4 py-3">
                            {{ company.approver?.name ?? '—' }}
                        </td>
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(company.approved_at) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <DropdownMenu
                                v-if="
                                    company.status === 'pending' ||
                                    company.status === 'approved'
                                "
                            >
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        :aria-label="`Actions for ${company.name}`"
                                    >
                                        <EllipsisVertical class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        v-if="company.status === 'pending'"
                                        @click="approve(company)"
                                    >
                                        Approve
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="company.status === 'pending'"
                                        class="text-destructive focus:text-destructive"
                                        @click="openReject(company)"
                                    >
                                        Reject
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="company.status === 'approved'"
                                        class="text-destructive focus:text-destructive"
                                        @click="suspend(company)"
                                    >
                                        Suspend
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                    </tr>
                    <tr v-if="companies.data.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No companies match the current filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ListPagination :paginator="companies" />

        <RejectReasonDialog
            v-model:open="rejectOpen"
            :title="
                rejectTarget ? `Reject ${rejectTarget.name}?` : 'Reject company'
            "
            description="Provide a clear reason. This will be shown to the company."
            confirm-label="Reject company"
            @confirm="confirmReject"
        />
    </div>
</template>
