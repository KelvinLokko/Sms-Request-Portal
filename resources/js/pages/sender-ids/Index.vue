<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, destroy, edit, index } from '@/routes/sender-ids';

type SenderIdRow = {
    id: number;
    value: string;
    status: string;
    status_label: string;
    has_document: boolean;
    rejection_reason: string | null;
    created_at: string | null;
    document_url: string | null;
};

defineProps<{
    senderIds: {
        data: SenderIdRow[];
        links: unknown[];
    };
    companyStatus: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Sender IDs', href: index() },
        ],
    },
});


function deleteSenderId(id: number) {
    if (!confirm('Delete this sender ID?')) {
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
                description="Register names recipients will see (max 11 characters). Editing an approved ID resets it to pending."
            />
            <Button as-child>
                <Link :href="create()">Register sender ID</Link>
            </Button>
        </div>

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
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="edit(row.id)">Edit</Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="deleteSenderId(row.id)"
                                >
                                    Delete
                                </Button>
                            </div>
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
    </div>
</template>
