<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight, CreditCard } from '@lucide/vue';
import ServiceIcon from '@/components/portal/ServiceIcon.vue';
import StatusBadge from '@/components/portal/StatusBadge.vue';
import { formatDate, formatMoney } from '@/lib/portal';
import { dashboard } from '@/routes';
import { checkout } from '@/routes/payments';
import { index as requestsIndex, show as showRequest } from '@/routes/requests';
import { index as servicesIndex, show as showService } from '@/routes/services';
import type { Service, ServiceRequest } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

defineProps<{
    stats: {
        total: number;
        awaiting_payment: number;
        action_required: number;
        in_progress: number;
        completed: number;
    };
    recent: ServiceRequest[];
    services: Service[];
}>();
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Welcome back, {{ $page.props.auth.user.name.split(' ')[0] }}
            </h1>
            <p class="text-sm text-muted-foreground">
                Apply for a service or follow up on your applications.
            </p>
        </div>

        <div
            v-if="stats.action_required"
            class="flex items-start gap-3 rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900"
        >
            <AlertTriangle class="mt-0.5 size-4 shrink-0" />
            <p class="flex-1">
                <strong>{{ stats.action_required }}</strong>
                application{{ stats.action_required > 1 ? 's need' : ' needs' }}
                more information from you.
            </p>
            <Link
                :href="requestsIndex({ query: { status: 'info_requested' } })"
                class="font-medium underline underline-offset-4"
                >Review</Link
            >
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div
                v-for="card in [
                    { label: 'Total applications', value: stats.total },
                    {
                        label: 'Awaiting payment',
                        value: stats.awaiting_payment,
                    },
                    { label: 'In progress', value: stats.in_progress },
                    { label: 'Approved', value: stats.completed },
                ]"
                :key="card.label"
                class="rounded-xl border p-4"
            >
                <p class="text-sm text-muted-foreground">{{ card.label }}</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">
                    {{ card.value }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-xl border lg:col-span-2">
                <div class="flex items-center justify-between border-b p-4">
                    <h2 class="font-semibold">Recent applications</h2>
                    <Link
                        :href="requestsIndex()"
                        class="text-sm text-primary hover:underline"
                        >View all</Link
                    >
                </div>
                <ul v-if="recent.length" class="divide-y">
                    <li
                        v-for="item in recent"
                        :key="item.id"
                        class="flex flex-wrap items-center gap-3 p-4"
                    >
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="showRequest(item.reference)"
                                class="font-medium hover:text-primary"
                                >{{ item.service?.name }}</Link
                            >
                            <p class="text-xs text-muted-foreground">
                                {{ item.reference }} ·
                                {{ formatDate(item.created_at) }} ·
                                {{ formatMoney(item.fees.total) }}
                            </p>
                        </div>
                        <StatusBadge
                            :value="item.status.value"
                            :label="item.status.label"
                        />
                        <Link
                            v-if="item.status.value === 'pending_payment'"
                            :href="checkout(item.reference)"
                            class="inline-flex items-center gap-1 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground"
                        >
                            <CreditCard class="size-3.5" /> Pay now
                        </Link>
                    </li>
                </ul>
                <div
                    v-else
                    class="p-8 text-center text-sm text-muted-foreground"
                >
                    You have not applied for anything yet.
                    <Link :href="servicesIndex()" class="text-primary underline"
                        >Browse services</Link
                    >
                </div>
            </section>

            <section class="rounded-xl border">
                <div class="border-b p-4">
                    <h2 class="font-semibold">Start a new application</h2>
                </div>
                <ul class="divide-y">
                    <li v-for="service in services" :key="service.id">
                        <Link
                            :href="showService(service.slug)"
                            class="flex items-center gap-3 p-4 hover:bg-accent"
                        >
                            <span
                                class="flex size-9 items-center justify-center rounded-lg bg-accent text-primary [&>svg]:size-4"
                            >
                                <ServiceIcon :icon="service.icon" />
                            </span>
                            <span class="flex-1 text-sm font-medium">{{
                                service.name
                            }}</span>
                            <ArrowRight class="size-4 text-muted-foreground" />
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
