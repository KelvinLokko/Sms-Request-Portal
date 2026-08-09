<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type FilterStatusOption = {
    value: string;
    label: string;
};

export type ListFilterValues = {
    status: string | null;
    from: string | null;
    to: string | null;
    q: string | null;
};

const props = withDefaults(
    defineProps<{
        status?: string | null;
        from?: string | null;
        to?: string | null;
        q?: string | null;
        statusOptions?: FilterStatusOption[];
        showSearch?: boolean;
        searchPlaceholder?: string;
        statusLabel?: string;
        fromLabel?: string;
        toLabel?: string;
    }>(),
    {
        status: null,
        from: null,
        to: null,
        q: null,
        statusOptions: () => [],
        showSearch: false,
        searchPlaceholder: 'Search…',
        statusLabel: 'Status',
        fromLabel: 'From',
        toLabel: 'To',
    },
);

const emit = defineEmits<{
    apply: [values: ListFilterValues];
    reset: [];
}>();

const status = ref(props.status ?? 'all');
const from = ref(props.from ?? '');
const to = ref(props.to ?? '');
const q = ref(props.q ?? '');

watch(
    () => [props.status, props.from, props.to, props.q] as const,
    ([nextStatus, nextFrom, nextTo, nextQ]) => {
        status.value = nextStatus ?? 'all';
        from.value = nextFrom ?? '';
        to.value = nextTo ?? '';
        q.value = nextQ ?? '';
    },
);

function apply() {
    emit('apply', {
        status:
            status.value === 'all' || status.value === '' ? null : status.value,
        from: from.value || null,
        to: to.value || null,
        q: q.value.trim() || null,
    });
}

function reset() {
    status.value = 'all';
    from.value = '';
    to.value = '';
    q.value = '';
    emit('reset');
}
</script>

<template>
    <div
        class="flex flex-col gap-3 rounded-xl border bg-card p-4 shadow-xs sm:flex-row sm:flex-wrap sm:items-end"
    >
        <div
            v-if="statusOptions.length"
            class="grid min-w-[10rem] flex-1 gap-1.5"
        >
            <Label class="text-xs text-muted-foreground">{{
                statusLabel
            }}</Label>
            <Select v-model="status">
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem
                        v-for="option in statusOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid min-w-[10rem] flex-1 gap-1.5">
            <Label class="text-xs text-muted-foreground">{{ fromLabel }}</Label>
            <Input v-model="from" type="date" />
        </div>

        <div class="grid min-w-[10rem] flex-1 gap-1.5">
            <Label class="text-xs text-muted-foreground">{{ toLabel }}</Label>
            <Input v-model="to" type="date" />
        </div>

        <div v-if="showSearch" class="grid min-w-[14rem] flex-[1.5] gap-1.5">
            <Label class="text-xs text-muted-foreground">Search</Label>
            <Input
                v-model="q"
                type="search"
                :placeholder="searchPlaceholder"
                @keydown.enter.prevent="apply"
            />
        </div>

        <div class="flex gap-2">
            <Button type="button" @click="apply">Apply</Button>
            <Button type="button" variant="outline" @click="reset"
                >Reset</Button
            >
        </div>
    </div>
</template>
