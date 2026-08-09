<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/admin/analytics';

Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Legend,
    Tooltip,
);

type SeriesPoint = {
    month: string;
    label: string;
    value: number | null;
    formatted: string;
};

const props = defineProps<{
    summary: {
        pending_review: number;
        awaiting_fulfilment: number;
        pending_payments: number;
        fulfilled: number;
        revenue: string;
        billed_sms: string;
        provider_cost: string;
        profit: string;
        is_loss: boolean;
        margin_percent: number | null;
        provider_rate: string | null;
        sms_volume: number;
    };
    monthly: {
        revenue: SeriesPoint[];
        profit: SeriesPoint[];
        requests: SeriesPoint[];
        sms_volume: SeriesPoint[];
    };
    growth: SeriesPoint[];
    avg_turnaround_hours: number | null;
    range: { from: string; to: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Analytics', href: index() }],
    },
});

const revenueCanvas = ref<HTMLCanvasElement | null>(null);
const profitCanvas = ref<HTMLCanvasElement | null>(null);
const requestsCanvas = ref<HTMLCanvasElement | null>(null);
const smsCanvas = ref<HTMLCanvasElement | null>(null);
const growthCanvas = ref<HTMLCanvasElement | null>(null);

let charts: Chart[] = [];

function destroyCharts() {
    charts.forEach((chart) => chart.destroy());
    charts = [];
}

function buildCharts() {
    destroyCharts();

    if (revenueCanvas.value) {
        charts.push(
            new Chart(revenueCanvas.value, {
                type: 'bar',
                data: {
                    labels: props.monthly.revenue.map((r) => r.label),
                    datasets: [
                        {
                            label: 'Revenue (pesewas)',
                            data: props.monthly.revenue.map(
                                (r) => r.value ?? 0,
                            ),
                            backgroundColor: 'rgba(15, 118, 110, 0.7)',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                },
            }),
        );
    }

    if (profitCanvas.value) {
        charts.push(
            new Chart(profitCanvas.value, {
                type: 'bar',
                data: {
                    labels: props.monthly.profit.map((r) => r.label),
                    datasets: [
                        {
                            label: 'Profit (pesewas)',
                            data: props.monthly.profit.map((r) => r.value ?? 0),
                            backgroundColor: 'rgba(22, 163, 74, 0.7)',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                },
            }),
        );
    }

    if (requestsCanvas.value) {
        charts.push(
            new Chart(requestsCanvas.value, {
                type: 'line',
                data: {
                    labels: props.monthly.requests.map((r) => r.label),
                    datasets: [
                        {
                            label: 'Submitted requests',
                            data: props.monthly.requests.map(
                                (r) => r.value ?? 0,
                            ),
                            borderColor: 'rgb(37, 99, 235)',
                            tension: 0.25,
                        },
                    ],
                },
                options: { responsive: true },
            }),
        );
    }

    if (smsCanvas.value) {
        charts.push(
            new Chart(smsCanvas.value, {
                type: 'bar',
                data: {
                    labels: props.monthly.sms_volume.map((r) => r.label),
                    datasets: [
                        {
                            label: 'SMS volume (recipients × pages)',
                            data: props.monthly.sms_volume.map(
                                (r) => r.value ?? 0,
                            ),
                            backgroundColor: 'rgba(180, 83, 9, 0.7)',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                },
            }),
        );
    }

    if (growthCanvas.value) {
        charts.push(
            new Chart(growthCanvas.value, {
                type: 'line',
                data: {
                    labels: props.growth.map((r) => r.label),
                    datasets: [
                        {
                            label: 'MoM growth %',
                            data: props.growth.map((r) => r.value ?? 0),
                            borderColor: 'rgb(124, 58, 237)',
                            tension: 0.25,
                        },
                    ],
                },
                options: { responsive: true },
            }),
        );
    }
}

onMounted(buildCharts);
watch(
    () => [props.monthly, props.growth],
    () => buildCharts(),
    { deep: true },
);
onBeforeUnmount(destroyCharts);
</script>

<template>
    <Head title="Analytics" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Analytics"
            :description="`Live database totals from ${range.from} to ${range.to} (invoices, campaigns, and payments — not demo charts).`"
        />

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Pending review</p>
                <p class="mt-1 text-2xl font-medium">
                    {{ summary.pending_review }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Awaiting fulfilment</p>
                <p class="mt-1 text-2xl font-medium">
                    {{ summary.awaiting_fulfilment }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Pending payments</p>
                <p class="mt-1 text-2xl font-medium">
                    {{ summary.pending_payments }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Fulfilled</p>
                <p class="mt-1 text-2xl font-medium">{{ summary.fulfilled }}</p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Client SMS billed</p>
                <p class="mt-1 text-2xl font-medium">
                    {{ summary.billed_sms }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Provider cost</p>
                <p class="mt-1 text-2xl font-medium">
                    {{ summary.provider_cost }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Realized profit</p>
                <p
                    class="mt-1 text-2xl font-medium"
                    :class="
                        summary.is_loss
                            ? 'text-destructive'
                            : 'text-emerald-700 dark:text-emerald-400'
                    "
                >
                    {{ summary.profit }}
                </p>
                <p
                    v-if="summary.margin_percent !== null"
                    class="mt-1 text-xs text-muted-foreground"
                >
                    {{ summary.margin_percent }}% margin
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Avg turnaround</p>
                <p class="mt-1 text-2xl font-medium">
                    {{
                        avg_turnaround_hours === null
                            ? '—'
                            : `${avg_turnaround_hours}h`
                    }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border p-4">
                <h2 class="mb-3 text-sm font-medium">Monthly paid revenue</h2>
                <canvas
                    ref="revenueCanvas"
                    aria-label="Monthly revenue chart"
                />
            </section>
            <section class="rounded-xl border p-4">
                <h2 class="mb-3 text-sm font-medium">
                    Monthly realized profit
                </h2>
                <canvas ref="profitCanvas" aria-label="Monthly profit chart" />
            </section>
            <section class="rounded-xl border p-4">
                <h2 class="mb-3 text-sm font-medium">Request volume</h2>
                <canvas
                    ref="requestsCanvas"
                    aria-label="Request volume chart"
                />
            </section>
            <section class="rounded-xl border p-4">
                <h2 class="mb-3 text-sm font-medium">SMS volume</h2>
                <canvas ref="smsCanvas" aria-label="SMS volume chart" />
            </section>
            <section class="rounded-xl border p-4 lg:col-span-2">
                <h2 class="mb-3 text-sm font-medium">Monthly growth</h2>
                <canvas ref="growthCanvas" aria-label="Growth chart" />
            </section>
        </div>
    </div>
</template>
