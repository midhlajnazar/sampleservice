<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import ServiceIcon from '@/components/portal/ServiceIcon.vue';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/portal';
import admin from '@/routes/admin';
import type { Service } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Back office', href: admin.dashboard() },
            { title: 'Services', href: admin.services.index() },
        ],
    },
});

defineProps<{
    services: Service[];
}>();

function toggle(service: Service) {
    router.post(
        admin.services.toggle.url(service.slug),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Manage services" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Manage services
            </h1>
            <p class="text-sm text-muted-foreground">
                Change fees, processing times, and the application form, or take
                a service offline.
            </p>
        </div>

        <ul class="divide-y rounded-xl border">
            <li
                v-for="service in services"
                :key="service.id"
                class="flex flex-wrap items-center gap-4 p-4"
            >
                <span
                    class="flex size-10 items-center justify-center rounded-lg bg-accent text-primary [&>svg]:size-5"
                >
                    <ServiceIcon :icon="service.icon" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-medium">
                        {{ service.name }}
                        <span
                            class="ml-2 rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="
                                service.is_active
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-neutral-100 text-neutral-500'
                            "
                            >{{ service.is_active ? 'Live' : 'Offline' }}</span
                        >
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ service.category }} ·
                        {{ formatMoney(service.base_fee) }} +
                        {{ service.vat_rate }}% VAT ·
                        {{ service.processing_days }} day(s) ·
                        {{ service.requests_count }} request(s)
                    </p>
                </div>
                <Button variant="outline" size="sm" @click="toggle(service)">
                    {{ service.is_active ? 'Take offline' : 'Make live' }}
                </Button>
                <Button size="sm" as-child>
                    <Link :href="admin.services.edit(service.slug)">Edit</Link>
                </Button>
            </li>
        </ul>
    </div>
</template>
