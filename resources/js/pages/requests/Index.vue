<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/components/portal/Pagination.vue';
import StatusBadge from '@/components/portal/StatusBadge.vue';
import { formatDate, formatMoney } from '@/lib/portal';
import { checkout } from '@/routes/payments';
import { index as requestsIndex, show as showRequest } from '@/routes/requests';
import { index as servicesIndex } from '@/routes/services';
import type { Paginated, ServiceRequest, Status } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My applications', href: requestsIndex() }],
    },
});

defineProps<{
    requests: Paginated<ServiceRequest>;
    statuses: Status[];
    filters: { status: string | null };
}>();

function filter(status: string) {
    router.get(
        requestsIndex.url({ query: status ? { status } : {} }),
        {},
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="My applications" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    My applications
                </h1>
                <p class="text-sm text-muted-foreground">
                    Track the progress of everything you have applied for.
                </p>
            </div>
            <select
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                :value="filters.status ?? ''"
                aria-label="Filter by status"
                @change="filter(($event.target as HTMLSelectElement).value)"
            >
                <option value="">All statuses</option>
                <option v-for="s in statuses" :key="s.value" :value="s.value">
                    {{ s.label }}
                </option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium">Service</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Created</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="item in requests.data"
                        :key="item.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-mono text-xs">
                            <Link
                                :href="showRequest(item.reference)"
                                class="text-primary hover:underline"
                                >{{ item.reference }}</Link
                            >
                        </td>
                        <td class="px-4 py-3">{{ item.service?.name }}</td>
                        <td class="px-4 py-3">
                            <StatusBadge
                                :value="item.status.value"
                                :label="item.status.label"
                            />
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ formatMoney(item.fees.total) }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ formatDate(item.created_at) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                v-if="item.status.value === 'pending_payment'"
                                :href="checkout(item.reference)"
                                class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground"
                                >Pay now</Link
                            >
                            <Link
                                v-else
                                :href="showRequest(item.reference)"
                                class="text-sm text-primary hover:underline"
                                >View</Link
                            >
                        </td>
                    </tr>
                    <tr v-if="!requests.data.length">
                        <td
                            colspan="6"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No applications found.
                            <Link
                                :href="servicesIndex()"
                                class="text-primary underline"
                                >Apply for a service</Link
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :meta="requests.meta" />
    </div>
</template>
