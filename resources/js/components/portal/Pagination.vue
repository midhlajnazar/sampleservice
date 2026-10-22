<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Paginated } from '@/types';

defineProps<{
    meta: Paginated<unknown>['meta'];
}>();
</script>

<template>
    <div
        v-if="meta.last_page > 1"
        class="flex flex-col items-center justify-between gap-3 text-sm sm:flex-row"
    >
        <p class="text-muted-foreground">
            Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}
        </p>
        <nav class="flex flex-wrap gap-1">
            <template v-for="(link, i) in meta.links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="rounded-md border px-3 py-1.5"
                    :class="
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'hover:bg-accent'
                    "
                >
                    <span v-html="link.label" />
                </Link>
                <span
                    v-else
                    class="rounded-md border px-3 py-1.5 text-muted-foreground opacity-50"
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
