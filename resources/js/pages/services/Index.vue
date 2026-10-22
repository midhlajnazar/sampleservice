<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clock } from '@lucide/vue';
import ServiceIcon from '@/components/portal/ServiceIcon.vue';
import { formatMoney } from '@/lib/portal';
import { index as servicesIndex, show as showService } from '@/routes/services';
import type { Service } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Services', href: servicesIndex() }],
    },
});

defineProps<{
    services: Service[];
    categories: string[];
    filters: { category: string | null };
}>();
</script>

<template>
    <Head title="Services" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Services</h1>
            <p class="text-sm text-muted-foreground">
                Choose a service to see requirements, fees and how to apply.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <Link
                :href="servicesIndex()"
                class="rounded-full border px-3 py-1 text-sm"
                :class="
                    !filters.category
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'hover:bg-accent'
                "
                >All</Link
            >
            <Link
                v-for="category in categories"
                :key="category"
                :href="servicesIndex({ query: { category } })"
                class="rounded-full border px-3 py-1 text-sm"
                :class="
                    filters.category === category
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'hover:bg-accent'
                "
                >{{ category }}</Link
            >
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="service in services"
                :key="service.id"
                :href="showService(service.slug)"
                class="group flex flex-col rounded-xl border p-5 transition hover:border-primary hover:shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-10 items-center justify-center rounded-lg bg-accent text-primary [&>svg]:size-5"
                    >
                        <ServiceIcon :icon="service.icon" />
                    </span>
                    <div>
                        <h2 class="font-semibold group-hover:text-primary">
                            {{ service.name }}
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            {{ service.category }}
                        </p>
                    </div>
                </div>
                <p class="mt-3 flex-1 text-sm text-muted-foreground">
                    {{ service.summary }}
                </p>
                <div
                    class="mt-4 flex items-center justify-between border-t pt-3 text-sm"
                >
                    <span class="font-medium"
                        >From {{ formatMoney(service.base_fee) }}</span
                    >
                    <span
                        class="inline-flex items-center gap-1 text-muted-foreground"
                    >
                        <Clock class="size-3.5" />
                        {{ service.processing_days }} day{{
                            service.processing_days > 1 ? 's' : ''
                        }}
                    </span>
                </div>
            </Link>
        </div>
    </div>
</template>
