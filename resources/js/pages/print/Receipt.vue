<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FeeSummary from '@/components/portal/FeeSummary.vue';
import { formatDate, formatMoney } from '@/lib/portal';
import type { ServiceRequest } from '@/types';

const props = defineProps<{
    serviceRequest: ServiceRequest;
}>();

const payment = computed(() =>
    props.serviceRequest.payments?.find((p) =>
        ['paid', 'refunded'].includes(p.status),
    ),
);

const printPage = () => window.print();
</script>

<template>
    <Head :title="`Receipt ${serviceRequest.reference}`" />

    <div class="min-h-screen bg-neutral-100 py-8 print:bg-white print:py-0">
        <div class="mx-auto mb-4 flex max-w-2xl justify-end px-4 print:hidden">
            <button
                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                @click="printPage"
            >
                <Printer class="size-4" /> Print / save as PDF
            </button>
        </div>

        <article
            class="mx-auto max-w-2xl bg-white p-10 shadow-sm ring-1 ring-neutral-200 print:shadow-none print:ring-0"
        >
            <header class="flex items-start justify-between border-b pb-6">
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <span
                        class="flex size-9 items-center justify-center rounded-md bg-primary text-white"
                    >
                        <AppLogoIcon class="size-5 fill-current" />
                    </span>
                    {{ $page.props.name }}
                </div>
                <div class="text-right">
                    <h1 class="text-xl font-semibold">Payment receipt</h1>
                    <p class="text-sm text-neutral-500">
                        {{ payment?.transaction_reference }}
                    </p>
                </div>
            </header>

            <dl class="grid grid-cols-2 gap-4 py-6 text-sm">
                <div>
                    <dt class="text-neutral-500">Billed to</dt>
                    <dd class="font-medium">
                        {{ serviceRequest.applicant?.name }}
                    </dd>
                    <dd>{{ serviceRequest.applicant?.email }}</dd>
                </div>
                <div class="text-right">
                    <dt class="text-neutral-500">Paid on</dt>
                    <dd class="font-medium">
                        {{ formatDate(payment?.paid_at, true) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-neutral-500">Application</dt>
                    <dd class="font-medium">{{ serviceRequest.reference }}</dd>
                    <dd>{{ serviceRequest.service?.name }}</dd>
                </div>
                <div class="text-right">
                    <dt class="text-neutral-500">Payment method</dt>
                    <dd class="font-medium">
                        {{ payment?.card_brand }} ••••
                        {{ payment?.card_last4 }}
                    </dd>
                    <dd>Auth code {{ payment?.authorization_code }}</dd>
                </div>
            </dl>

            <FeeSummary :fees="serviceRequest.fees" />

            <p
                v-if="payment?.status === 'refunded'"
                class="mt-6 rounded-md bg-neutral-100 p-3 text-sm"
            >
                This payment of {{ formatMoney(payment.amount) }} was refunded
                on {{ formatDate(payment.refunded_at) }}.
            </p>

            <footer class="mt-10 border-t pt-4 text-xs text-neutral-500">
                This is a computer-generated receipt from a demo system and does
                not require a signature.
            </footer>
        </article>
    </div>
</template>
