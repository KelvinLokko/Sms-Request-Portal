<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SenderIdReviewController from '@/actions/App/Http/Controllers/Admin/SenderIdReviewController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/admin/sender-ids';

type Row = {
    id: number;
    value: string;
    company: { id: number; name: string };
    requester: { name: string; email: string };
    has_document: boolean;
    uses_company_letterhead: boolean;
    created_at: string | null;
    document_url: string | null;
};

defineProps<{
    senderIds: { data: Row[] };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Sender ID review', href: index() }],
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const rejectReason = ref<Record<number, string>>({});

function approve(id: number) {
    router.post(SenderIdReviewController.approve.url(id));
}

function reject(id: number) {
    const reason = rejectReason.value[id]?.trim();
    if (!reason) {
        alert('A rejection reason is required.');
        return;
    }
    router.post(SenderIdReviewController.reject.url(id), {
        rejection_reason: reason,
    });
}
</script>

<template>
    <Head title="Sender ID review" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Sender ID review"
            description="Approve sender IDs before clients can use them on campaign requests."
        />

        <p
            v-if="flashSuccess"
            class="rounded-lg border border-green-500/30 bg-green-500/10 px-3 py-2 text-sm"
            role="status"
        >
            {{ flashSuccess }}
        </p>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">Sender ID</th>
                        <th class="px-4 py-3 font-medium">Company</th>
                        <th class="px-4 py-3 font-medium">Document</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in senderIds.data"
                        :key="row.id"
                        class="border-b align-top last:border-0"
                    >
                        <td class="px-4 py-3 font-mono">{{ row.value }}</td>
                        <td class="px-4 py-3">
                            <div>{{ row.company.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ row.requester.name }} · {{ row.requester.email }}
                            </div>
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
                        <td class="px-4 py-3">
                            <div class="flex max-w-sm flex-col gap-2">
                                <Button size="sm" @click="approve(row.id)">
                                    Approve
                                </Button>
                                <textarea
                                    v-model="rejectReason[row.id]"
                                    class="min-h-16 w-full rounded-md border bg-background px-2 py-1 text-sm"
                                    placeholder="Rejection reason"
                                />
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="reject(row.id)"
                                >
                                    Reject
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="senderIds.data.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No pending sender IDs.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
