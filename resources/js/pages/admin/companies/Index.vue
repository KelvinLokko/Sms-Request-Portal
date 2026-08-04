<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CompanyController from '@/actions/App/Http/Controllers/Admin/CompanyController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
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
};

const props = defineProps<{
    companies: { data: CompanyRow[] };
    filters: { status: string | null };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Companies', href: index() }],
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const rejectReason = ref<Record<number, string>>({});

function filterStatus(status: string | null) {
    router.get(index.url(), status ? { status } : {}, { preserveState: true });
}

function approve(id: number) {
    router.post(CompanyController.approve.url(id));
}

function reject(id: number) {
    const reason = rejectReason.value[id]?.trim();
    if (!reason) {
        alert('A rejection reason is required.');
        return;
    }
    router.post(CompanyController.reject.url(id), { rejection_reason: reason });
}

function suspend(id: number) {
    if (!confirm('Suspend this company?')) {
        return;
    }
    router.post(CompanyController.suspend.url(id));
}
</script>

<template>
    <Head title="Companies" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Companies"
            description="Approve or reject company registrations before they can submit campaigns."
        />

        <p
            v-if="flashSuccess"
            class="rounded-lg border border-green-500/30 bg-green-500/10 px-3 py-2 text-sm"
            role="status"
        >
            {{ flashSuccess }}
        </p>

        <div class="flex flex-wrap gap-2">
            <Button
                size="sm"
                :variant="!filters.status ? 'default' : 'outline'"
                @click="filterStatus(null)"
            >
                All
            </Button>
            <Button
                v-for="status in ['pending', 'approved', 'rejected', 'suspended']"
                :key="status"
                size="sm"
                :variant="filters.status === status ? 'default' : 'outline'"
                @click="filterStatus(status)"
            >
                {{ status }}
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Users</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="company in companies.data"
                        :key="company.id"
                        class="border-b align-top last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ company.name }}</div>
                            <div class="text-muted-foreground">
                                {{ company.email }}
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ company.status_label }}</td>
                        <td class="px-4 py-3">{{ company.users_count }}</td>
                        <td class="px-4 py-3">
                            <div
                                v-if="company.status === 'pending'"
                                class="flex max-w-sm flex-col gap-2"
                            >
                                <Button size="sm" @click="approve(company.id)">
                                    Approve
                                </Button>
                                <textarea
                                    v-model="rejectReason[company.id]"
                                    class="min-h-16 w-full rounded-md border bg-background px-2 py-1 text-sm"
                                    placeholder="Rejection reason"
                                />
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="reject(company.id)"
                                >
                                    Reject
                                </Button>
                            </div>
                            <Button
                                v-else-if="company.status === 'approved'"
                                size="sm"
                                variant="outline"
                                @click="suspend(company.id)"
                            >
                                Suspend
                            </Button>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
