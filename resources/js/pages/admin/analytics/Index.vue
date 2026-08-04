<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue';
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
        sms_volume: number;
    };
    monthly: {
        revenue: SeriesPoint[];
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

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const revenueCanvas = ref<HTMLCanvasElement | null>(null);
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
                            data: props.monthly.revenue.map((r) => r.value ?? 0),
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

    if (requestsCanvas.value) {
        charts.push(
            new Chart(requestsCanvas.value, {
                type: 'line',
                data: {
                    labels: props.monthly.requests.map((r) => r.label),
                    datasets: [
                        {
                            label: 'Submitted requests',
                            data: props.monthly.requests.map((r) => r.value ?? 0),
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
                            data: props.monthly.sms_volume.map((r) => r.value ?? 0),
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
            :description="`Aggregates from ${range.from} to ${range.to}. Values computed in SQL.`"
        />

        <p
            v-if="flashSuccess"
            class="rounded-lg border border-green-500/30 bg-green-500/10 px-3 py-2 text-sm"
            role="status"
        >
            {{ flashSuccess }}
        </p>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Pending review</p>
                <p class="mt-1 text-2xl font-medium">{{ summary.pending_review }}</p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Awaiting fulfilment</p>
                <p class="mt-1 text-2xl font-medium">{{ summary.awaiting_fulfilment }}</p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Pending payments</p>
                <p class="mt-1 text-2xl font-medium">{{ summary.pending_payments }}</p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Fulfilled</p>
                <p class="mt-1 text-2xl font-medium">{{ summary.fulfilled }}</p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Paid revenue</p>
                <p class="mt-1 text-2xl font-medium">{{ summary.revenue }}</p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs text-muted-foreground">Avg turnaround</p>
                <p class="mt-1 text-2xl font-medium">
                    {{ avg_turnaround_hours === null ? '—' : `${avg_turnaround_hours}h` }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border p-4">
                <h2 class="mb-3 text-sm font-medium">Monthly revenue</h2>
                <canvas ref="revenueCanvas" aria-label="Monthly revenue chart" />
            </section>
            <section class="rounded-xl border p-4">
                <h2 class="mb-3 text-sm font-medium">Request volume</h2>
                <canvas ref="requestsCanvas" aria-label="Request volume chart" />
            </section>
            <section class="rounded-xl border p-4">
                <h2 class="mb-3 text-sm font-medium">SMS volume</h2>
                <canvas ref="smsCanvas" aria-label="SMS volume chart" />
            </section>
            <section class="rounded-xl border p-4">
                <h2 class="mb-3 text-sm font-medium">Monthly growth</h2>
                <canvas ref="growthCanvas" aria-label="Growth chart" />
            </section>
        </div>
    </div>
</template>
