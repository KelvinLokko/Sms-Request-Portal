<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import type { Paginated, PaginationLink } from '@/types';

const props = defineProps<{
    paginator: Paginated<unknown>;
}>();

const summary = computed(() => {
    const { from, to, total } = props.paginator;

    if (total === 0 || from === null || to === null) {
        return null;
    }

    return `Showing ${from.toLocaleString()}–${to.toLocaleString()} of ${total.toLocaleString()}`;
});

const pageLinks = computed(() =>
    props.paginator.links.filter(
        (link) =>
            !link.label.includes('Previous') && !link.label.includes('Next'),
    ),
);

const showControls = computed(() => props.paginator.last_page > 1);

function labelText(link: PaginationLink): string {
    return link.label.replace(/&laquo;|&raquo;/g, '').trim();
}
</script>

<template>
    <div
        v-if="summary || showControls"
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <p v-if="summary" class="text-sm text-muted-foreground">
            {{ summary }}
        </p>
        <div v-else />

        <nav
            v-if="showControls"
            class="flex flex-wrap items-center gap-1"
            aria-label="Pagination"
        >
            <Button
                v-if="paginator.prev_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link
                    :href="paginator.prev_page_url"
                    preserve-scroll
                    preserve-state
                    aria-label="Previous page"
                >
                    <ChevronLeft class="size-4" />
                    <span class="sr-only sm:not-sr-only">Previous</span>
                </Link>
            </Button>
            <Button
                v-else
                variant="outline"
                size="sm"
                disabled
                aria-label="Previous page"
            >
                <ChevronLeft class="size-4" />
                <span class="sr-only sm:not-sr-only">Previous</span>
            </Button>

            <template
                v-for="(link, index) in pageLinks"
                :key="`${link.label}-${index}`"
            >
                <Button
                    v-if="link.url"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    class="min-w-9"
                    as-child
                >
                    <Link
                        :href="link.url"
                        preserve-scroll
                        preserve-state
                        :aria-current="link.active ? 'page' : undefined"
                    >
                        {{ labelText(link) }}
                    </Link>
                </Button>
                <Button
                    v-else
                    variant="outline"
                    size="sm"
                    class="min-w-9"
                    disabled
                >
                    {{ labelText(link) }}
                </Button>
            </template>

            <Button
                v-if="paginator.next_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link
                    :href="paginator.next_page_url"
                    preserve-scroll
                    preserve-state
                    aria-label="Next page"
                >
                    <span class="sr-only sm:not-sr-only">Next</span>
                    <ChevronRight class="size-4" />
                </Link>
            </Button>
            <Button
                v-else
                variant="outline"
                size="sm"
                disabled
                aria-label="Next page"
            >
                <span class="sr-only sm:not-sr-only">Next</span>
                <ChevronRight class="size-4" />
            </Button>
        </nav>
    </div>
</template>
