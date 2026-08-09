<script setup lang="ts">
import { CheckIcon, ChevronsUpDownIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
    ComboboxViewport,
} from '@/components/ui/combobox';
import { cn } from '@/lib/utils';

export type SearchableSelectOption = {
    value: string;
    label: string;
};

const props = withDefaults(
    defineProps<{
        options: SearchableSelectOption[];
        name?: string;
        id?: string;
        placeholder?: string;
        searchPlaceholder?: string;
        emptyText?: string;
        disabled?: boolean;
        class?: string;
    }>(),
    {
        placeholder: 'Select…',
        searchPlaceholder: 'Search…',
        emptyText: 'No results found.',
    },
);

const model = defineModel<string>({ default: '' });

const selected = computed(
    () => props.options.find((option) => option.value === model.value) ?? null,
);
</script>

<template>
    <div :class="cn('w-full', props.class)">
        <input v-if="name" type="hidden" :name="name" :value="model" />
        <Combobox v-model="model" :disabled="disabled">
            <ComboboxAnchor as-child class="w-full">
                <ComboboxTrigger as-child>
                    <Button
                        type="button"
                        variant="outline"
                        role="combobox"
                        :id="id"
                        :disabled="disabled"
                        class="h-9 w-full justify-between px-3 font-normal shadow-none"
                        :class="!selected ? 'text-muted-foreground' : ''"
                    >
                        <span class="truncate">
                            {{ selected?.label ?? placeholder }}
                        </span>
                        <ChevronsUpDownIcon class="size-4 shrink-0 opacity-50" />
                    </Button>
                </ComboboxTrigger>
            </ComboboxAnchor>

            <ComboboxList
                class="w-[var(--reka-combobox-trigger-width)]"
                align="start"
            >
                <ComboboxInput :placeholder="searchPlaceholder" />
                <ComboboxViewport>
                    <ComboboxEmpty>{{ emptyText }}</ComboboxEmpty>
                    <ComboboxGroup>
                        <ComboboxItem
                            v-for="option in options"
                            :key="option.value"
                            :value="option.value"
                            :text-value="option.label"
                        >
                            <span class="truncate">{{ option.label }}</span>
                            <ComboboxItemIndicator class="ml-auto">
                                <CheckIcon class="size-4" />
                            </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>
                </ComboboxViewport>
            </ComboboxList>
        </Combobox>
    </div>
</template>
