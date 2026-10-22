<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/components/portal/Pagination.vue';
import StatusBadge from '@/components/portal/StatusBadge.vue';
import { formatDate, formatMoney } from '@/lib/portal';
import admin from '@/routes/admin';
import type { Paginated, Payment } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Back office', href: admin.dashboard() },
            { title: 'Payments', href: admin.payments.index() },
        ],
    },
});

defineProps<{
    payments: Paginated<Payment>;
    statuses: { value: string; label: string }[];
    filters: { status: string | null };
}>();

function filter(status: string) {
    router.get(
        admin.payments.index.url({ query: status ? { status } : {} }),
        {},
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Payments" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Payments</h1>
                <p class="text-sm text-muted-foreground">
                    All transactions through the SamplePay test gateway.
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
                        <th class="px-4 py-3 font-medium">Transaction</th>
                        <th class="px-4 py-3 font-medium">Request</th>
                        <th class="px-4 py-3 font-medium">Applicant</th>
                        <th class="px-4 py-3 font-medium">Card</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="payment in payments.data" :key="payment.id">
                        <td class="px-4 py-3 font-mono text-xs">
                            {{ payment.transaction_reference }}
                        </td>
                        <td class="px-4 py-3">
                            <Link
                                v-if="payment.service_request"
                                :href="
                                    admin.requests.show(
                                        payment.service_request.reference,
                                    )
                                "
                                class="font-mono text-xs text-primary hover:underline"
                                >{{ payment.service_request.reference }}</Link
                            >
                            <p class="text-xs text-muted-foreground">
                                {{ payment.service_request?.service }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            {{ payment.service_request?.applicant }}
                        </td>
                        <td class="px-4 py-3">
                            {{ payment.card_brand }} ••••
                            {{ payment.card_last4 }}
                            <p
                                v-if="payment.failure_reason"
                                class="text-xs text-red-600"
                            >
                                {{ payment.failure_reason }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :value="payment.status" />
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ formatMoney(payment.amount) }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ formatDate(payment.created_at, true) }}
                        </td>
                    </tr>
                    <tr v-if="!payments.data.length">
                        <td
                            colspan="7"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No payments yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :meta="payments.meta" />
    </div>
</template>
