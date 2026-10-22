<script setup lang="ts">
import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Award,
    CreditCard,
    Download,
    ExternalLink,
    Receipt,
} from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import ActivityTimeline from '@/components/portal/ActivityTimeline.vue';
import FeeSummary from '@/components/portal/FeeSummary.vue';
import FileUpload from '@/components/portal/FileUpload.vue';
import StatusBadge from '@/components/portal/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatBytes, formatDate, formatMoney, isVisible } from '@/lib/portal';
import { checkout } from '@/routes/payments';
import {
    cancel,
    index as requestsIndex,
    print,
    respond,
} from '@/routes/requests';
import type { DocumentRequirement, ServiceRequest } from '@/types';

const props = defineProps<{
    serviceRequest: ServiceRequest;
    documentSchema: DocumentRequirement[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'My applications', href: requestsIndex() },
        { title: props.serviceRequest.reference, href: '#' },
    ],
});

const req = computed(() => props.serviceRequest);
const status = computed(() => req.value.status.value);

const currentDocuments = computed(() =>
    (req.value.documents ?? []).filter((d) => d.status !== 'superseded'),
);

const applicableDocuments = computed(() =>
    props.documentSchema.filter((d) =>
        isVisible(d.show_if, req.value.form_data),
    ),
);

const latestInfoRequest = computed(() =>
    req.value.activities?.find((a) => a.to_status?.value === 'info_requested'),
);

const hasPaid = computed(() =>
    (req.value.payments ?? []).some((p) =>
        ['paid', 'refunded'].includes(p.status),
    ),
);

const progress = ['Submitted', 'Under review', 'Decision', 'Completed'];
const progressIndex = computed(
    () =>
        ({
            pending_payment: -1,
            submitted: 0,
            under_review: 1,
            info_requested: 1,
            approved: 2,
            rejected: 2,
            completed: 3,
            cancelled: -1,
        })[status.value],
);

const response = useForm({
    comment: '',
    documents: {} as Record<string, File | null>,
});

function sendResponse() {
    response
        .transform((data) => ({
            comment: data.comment,
            documents: Object.fromEntries(
                Object.entries(data.documents).filter(([, f]) => f),
            ),
        }))
        .post(respond.url(req.value.reference), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => response.reset(),
        });
}

function cancelRequest() {
    if (confirm('Cancel this application? This cannot be undone.')) {
        router.post(
            cancel.url(req.value.reference),
            {},
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="req.reference" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-sm text-muted-foreground">
                    {{ req.reference }}
                </p>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ req.service?.name }}
                </h1>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                    <StatusBadge :value="status" :label="req.status.label" />
                    <span class="text-muted-foreground"
                        >Created {{ formatDate(req.created_at, true) }} via
                        {{ req.channel }}</span
                    >
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button v-if="hasPaid" variant="outline" as-child>
                    <a
                        :href="print.url([req.reference, 'receipt'])"
                        target="_blank"
                        ><Receipt class="size-4" /> Receipt</a
                    >
                </Button>
                <Button v-if="req.certificate_number" as-child>
                    <a
                        :href="print.url([req.reference, 'certificate'])"
                        target="_blank"
                        ><Award class="size-4" /> Certificate</a
                    >
                </Button>
            </div>
        </div>

        <!-- Progress -->
        <ol
            v-if="progressIndex >= 0"
            class="grid grid-cols-4 gap-2 text-center text-xs"
        >
            <li v-for="(label, i) in progress" :key="label">
                <div
                    class="h-1.5 rounded-full"
                    :class="
                        i <= progressIndex
                            ? status === 'rejected' && i === 2
                                ? 'bg-red-500'
                                : 'bg-primary'
                            : 'bg-muted'
                    "
                />
                <p
                    class="mt-1.5"
                    :class="
                        i <= progressIndex
                            ? 'font-medium'
                            : 'text-muted-foreground'
                    "
                >
                    {{ i === 2 && status === 'rejected' ? 'Rejected' : label }}
                </p>
            </li>
        </ol>

        <!-- Call to action banners -->
        <div
            v-if="status === 'pending_payment'"
            class="flex flex-wrap items-center gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4"
        >
            <CreditCard class="size-5 text-amber-700" />
            <div class="flex-1 text-sm text-amber-900">
                <p class="font-medium">Payment pending</p>
                <p>
                    Your application is saved but will not be processed until
                    you pay {{ formatMoney(req.fees.total) }}.
                </p>
            </div>
            <Button variant="outline" @click="cancelRequest">Cancel</Button>
            <Button as-child>
                <Link :href="checkout(req.reference)">Pay now</Link>
            </Button>
        </div>

        <div
            v-if="status === 'approved' || status === 'completed'"
            class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"
        >
            <p class="font-medium">
                Approved · Certificate {{ req.certificate_number }}
            </p>
            <p>
                Valid until {{ formatDate(req.valid_until) }}.
                {{ req.decision_note }}
            </p>
        </div>

        <div
            v-if="status === 'rejected'"
            class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900"
        >
            <p class="font-medium">Application rejected</p>
            <p>{{ req.decision_note }}</p>
            <p class="mt-1 text-red-700">Any payment made has been refunded.</p>
        </div>

        <section
            v-if="status === 'info_requested'"
            class="rounded-xl border border-orange-200 bg-orange-50/60 p-5"
        >
            <div class="flex items-start gap-3">
                <AlertTriangle class="mt-0.5 size-5 text-orange-600" />
                <div class="flex-1">
                    <h2 class="font-semibold text-orange-900">
                        More information needed
                    </h2>
                    <p class="text-sm whitespace-pre-line text-orange-900">
                        {{ latestInfoRequest?.comment }}
                    </p>
                </div>
            </div>
            <form class="mt-4 space-y-4" @submit.prevent="sendResponse">
                <div class="grid gap-2">
                    <label for="comment" class="text-sm font-medium"
                        >Your reply</label
                    >
                    <textarea
                        id="comment"
                        v-model="response.comment"
                        rows="3"
                        class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm"
                        placeholder="Explain what you have changed or attached"
                    />
                    <InputError :message="response.errors.comment" />
                </div>
                <details class="rounded-lg border bg-white p-4" open>
                    <summary class="cursor-pointer text-sm font-medium">
                        Replace or add documents
                    </summary>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <FileUpload
                            v-for="doc in applicableDocuments"
                            :key="doc.key"
                            v-model="response.documents[doc.key]"
                            :requirement="doc"
                            optional
                            :error="
                                (response.errors as Record<string, string>)[
                                    `documents.${doc.key}`
                                ]
                            "
                        />
                    </div>
                </details>
                <Button type="submit" :disabled="response.processing"
                    >Send response</Button
                >
            </form>
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-xl border p-5">
                    <h2 class="mb-4 font-semibold">Application details</h2>
                    <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        <div v-for="answer in req.answers" :key="answer.label">
                            <dt class="text-muted-foreground">
                                {{ answer.label }}
                            </dt>
                            <dd class="font-medium break-words">
                                {{ answer.value }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-xl border p-5">
                    <h2 class="mb-4 font-semibold">Documents</h2>
                    <ul class="divide-y text-sm">
                        <li
                            v-for="doc in currentDocuments"
                            :key="doc.id"
                            class="flex flex-wrap items-center gap-3 py-3"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">{{ doc.label }}</p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ doc.original_name }} ·
                                    {{ formatBytes(doc.size) }}
                                </p>
                                <p
                                    v-if="doc.review_note"
                                    class="text-xs text-red-600"
                                >
                                    {{ doc.review_note }}
                                </p>
                            </div>
                            <StatusBadge :value="doc.status" />
                            <a
                                :href="doc.url"
                                target="_blank"
                                class="rounded p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground"
                                :aria-label="`Open ${doc.label}`"
                                ><ExternalLink class="size-4"
                            /></a>
                            <a
                                :href="`${doc.url}?download=1`"
                                class="rounded p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground"
                                :aria-label="`Download ${doc.label}`"
                                ><Download class="size-4"
                            /></a>
                        </li>
                    </ul>
                </section>

                <section class="rounded-xl border p-5">
                    <h2 class="mb-4 font-semibold">History</h2>
                    <ActivityTimeline :activities="req.activities ?? []" />
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-xl border p-5">
                    <h2 class="mb-4 font-semibold">Fees</h2>
                    <FeeSummary :fees="req.fees" />
                </section>

                <section
                    v-if="req.payments?.length"
                    class="rounded-xl border p-5"
                >
                    <h2 class="mb-4 font-semibold">Payments</h2>
                    <ul class="space-y-3 text-sm">
                        <li
                            v-for="payment in req.payments"
                            :key="payment.id"
                            class="rounded-lg bg-muted/40 p-3"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span class="font-mono text-xs">{{
                                    payment.transaction_reference
                                }}</span>
                                <StatusBadge :value="payment.status" />
                            </div>
                            <p class="mt-1">
                                {{ formatMoney(payment.amount) }} ·
                                {{ payment.card_brand }} ••••
                                {{ payment.card_last4 }}
                            </p>
                            <p
                                v-if="payment.failure_reason"
                                class="text-xs text-red-600"
                            >
                                {{ payment.failure_reason }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(payment.created_at, true) }}
                            </p>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>
