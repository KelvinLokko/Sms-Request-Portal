<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/invoices';

type Row = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    total: string;
    campaign_reference: string | null;
    campaign_name: string | null;
    issued_at: string | null;
};

defineProps<{
    invoices: { data: Row[] };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Invoices', href: index() }],
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
</script>

<template>
    <Head title="Invoices" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Invoices"
            description="Issued invoices for your SMS campaign requests."
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
                        <th class="px-4 py-3 font-medium">Number</th>
                        <th class="px-4 py-3 font-medium">Campaign</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium" />
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in invoices.data"
                        :key="row.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3 font-mono text-xs">{{ row.number }}</td>
                        <td class="px-4 py-3">
                            <div>{{ row.campaign_reference }}</div>
                            <div class="text-muted-foreground">{{ row.campaign_name }}</div>
                        </td>
                        <td class="px-4 py-3">{{ row.status_label }}</td>
                        <td class="px-4 py-3">{{ row.total }}</td>
                        <td class="px-4 py-3 text-right">
                            <Button as-child size="sm" variant="outline">
                                <Link :href="show(row.id)">View</Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="invoices.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No invoices yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
