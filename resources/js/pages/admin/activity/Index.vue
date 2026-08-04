<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/activity';

type LogRow = {
    id: number;
    action: string;
    actor: { name: string; email: string } | null;
    company: { name: string } | null;
    subject_type: string | null;
    subject_id: number | null;
    properties: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
};

defineProps<{
    logs: { data: LogRow[] };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Audit log', href: index() }],
    },
});


function formatWhen(value: string): string {
    return new Date(value).toLocaleString();
}
</script>

<template>
    <Head title="Audit log" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Audit log"
            description="Append-only record of privileged actions with actor, IP, and before/after state."
        />

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full min-w-[52rem] text-left text-sm">
                <thead class="border-b bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 font-medium">When</th>
                        <th class="px-4 py-3 font-medium">Action</th>
                        <th class="px-4 py-3 font-medium">Actor</th>
                        <th class="px-4 py-3 font-medium">Subject</th>
                        <th class="px-4 py-3 font-medium">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in logs.data"
                        :key="row.id"
                        class="border-b align-top last:border-0"
                    >
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ formatWhen(row.created_at) }}
                            <div class="text-xs text-muted-foreground">
                                {{ row.ip_address ?? '—' }}
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ row.action }}</td>
                        <td class="px-4 py-3">
                            <div v-if="row.actor">
                                {{ row.actor.name }}
                                <div class="text-xs text-muted-foreground">
                                    {{ row.actor.email }}
                                </div>
                            </div>
                            <span v-else class="text-muted-foreground">System</span>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            <div>{{ row.company?.name ?? '—' }}</div>
                            <div class="text-muted-foreground">
                                {{ row.subject_type ?? '—' }}
                                <span v-if="row.subject_id">#{{ row.subject_id }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <pre
                                class="max-w-md overflow-x-auto whitespace-pre-wrap font-mono text-xs text-muted-foreground"
                            >{{ row.properties ? JSON.stringify(row.properties, null, 2) : '—' }}</pre>
                        </td>
                    </tr>
                    <tr v-if="logs.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No activity recorded yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
