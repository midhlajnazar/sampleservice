<script setup lang="ts">
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Check, ExternalLink, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import ActivityTimeline from '@/components/portal/ActivityTimeline.vue';
import FeeSummary from '@/components/portal/FeeSummary.vue';
import StatusBadge from '@/components/portal/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatBytes, formatDate, formatMoney } from '@/lib/portal';
import admin from '@/routes/admin';
import type { RequestDocument, ServiceRequest, Status } from '@/types';

const props = defineProps<{
    serviceRequest: ServiceRequest;
    transitions: Status[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Back office', href: admin.dashboard() },
        { title: 'Requests', href: admin.requests.index() },
        { title: props.serviceRequest.reference, href: '#' },
    ],
});

const req = computed(() => props.serviceRequest);
const showSuperseded = ref(false);

const documents = computed(() =>
    (req.value.documents ?? []).filter(
        (d) => showSuperseded.value || d.status !== 'superseded',
    ),
);

const sections = computed(() => {
    const grouped: Record<string, { label: string; value: string }[]> = {};

    for (const answer of req.value.answers ?? []) {
        (grouped[answer.section] ??= []).push(answer);
    }

    return grouped;
});

const actionLabels: Record<string, string> = {
    under_review: 'Start review',
    info_requested: 'Request more information',
    approved: 'Approve',
    rejected: 'Reject',
    completed: 'Mark as completed',
};

const actionStyles: Record<string, string> = {
    approved: 'bg-emerald-600 hover:bg-emerald-700 text-white',
    completed: 'bg-emerald-600 hover:bg-emerald-700 text-white',
    rejected: 'bg-red-600 hover:bg-red-700 text-white',
};

const transition = useForm({ status: '', comment: '' });
const needsComment = computed(() =>
    ['info_requested', 'rejected'].includes(transition.status),
);

function submitTransition(status: string) {
    if (transition.status !== status) {
        transition.status = status;
        transition.clearErrors();

        // Simple moves go straight through; others need a comment first.
        if (!['info_requested', 'rejected', 'approved'].includes(status)) {
            postTransition();
        }

        return;
    }

    postTransition();
}

function postTransition() {
    transition.post(admin.requests.transition.url(req.value.reference), {
        preserveScroll: true,
        onSuccess: () => transition.reset(),
    });
}

const note = useForm({ note: '' });

function addNote() {
    note.post(admin.requests.notes.url(req.value.reference), {
        preserveScroll: true,
        onSuccess: () => note.reset(),
    });
}

function reviewDocument(doc: RequestDocument, status: 'accepted' | 'rejected') {
    const reason =
        status === 'rejected'
            ? (prompt(`Why is "${doc.label}" rejected?`) ?? undefined)
            : undefined;

    if (status === 'rejected' && reason === undefined) {
        return;
    }

    router.patch(
        admin.documents.review.url(doc.id),
        { status, note: reason },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="`${req.reference} · Admin`" />

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
                    <StatusBadge
                        :value="req.status.value"
                        :label="req.status.label"
                    />
                    <span class="text-muted-foreground">
                        Submitted {{ formatDate(req.submitted_at, true) }} · via
                        {{ req.channel }} · assigned to
                        {{ req.assignee?.name ?? 'nobody' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section
                    v-for="(answers, title) in sections"
                    :key="title"
                    class="rounded-xl border p-5"
                >
                    <h2 class="mb-4 font-semibold">{{ title }}</h2>
                    <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        <div v-for="answer in answers" :key="answer.label">
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
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="font-semibold">Documents</h2>
                        <label
                            class="flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <input
                                v-model="showSuperseded"
                                type="checkbox"
                                class="accent-primary"
                            />
                            Show replaced versions
                        </label>
                    </div>
                    <ul class="divide-y text-sm">
                        <li
                            v-for="doc in documents"
                            :key="doc.id"
                            class="flex flex-wrap items-center gap-3 py-3"
                            :class="
                                doc.status === 'superseded' ? 'opacity-60' : ''
                            "
                        >
                            <div class="min-w-0 flex-1">
                                <a
                                    :href="doc.url"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 font-medium hover:text-primary"
                                    >{{ doc.label }}
                                    <ExternalLink class="size-3.5" />
                                </a>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ doc.original_name }} ·
                                    {{ formatBytes(doc.size) }} · uploaded
                                    {{ formatDate(doc.uploaded_at, true) }}
                                </p>
                                <p
                                    v-if="doc.review_note"
                                    class="text-xs text-red-600"
                                >
                                    {{ doc.review_note }}
                                </p>
                            </div>
                            <StatusBadge :value="doc.status" />
                            <template v-if="doc.status !== 'superseded'">
                                <Button
                                    size="icon"
                                    variant="outline"
                                    class="size-8"
                                    :aria-label="`Accept ${doc.label}`"
                                    :disabled="doc.status === 'accepted'"
                                    @click="reviewDocument(doc, 'accepted')"
                                >
                                    <Check class="size-4 text-emerald-600" />
                                </Button>
                                <Button
                                    size="icon"
                                    variant="outline"
                                    class="size-8"
                                    :aria-label="`Reject ${doc.label}`"
                                    :disabled="doc.status === 'rejected'"
                                    @click="reviewDocument(doc, 'rejected')"
                                >
                                    <X class="size-4 text-red-600" />
                                </Button>
                            </template>
                        </li>
                    </ul>
                </section>

                <section class="rounded-xl border p-5">
                    <h2 class="mb-4 font-semibold">Activity</h2>
                    <form class="mb-6 flex gap-2" @submit.prevent="addNote">
                        <input
                            v-model="note.note"
                            class="h-9 flex-1 rounded-md border border-input bg-transparent px-3 text-sm"
                            placeholder="Add an internal note (not visible to the applicant)"
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="note.processing || !note.note"
                            >Add note</Button
                        >
                    </form>
                    <ActivityTimeline :activities="req.activities ?? []" />
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-xl border p-5">
                    <h2 class="mb-1 font-semibold">Process request</h2>
                    <p
                        v-if="!transitions.length"
                        class="text-sm text-muted-foreground"
                    >
                        <template v-if="req.status.value === 'pending_payment'"
                            >Waiting for the applicant to pay.</template
                        >
                        <template v-else
                            >This request is closed. No further
                            actions.</template
                        >
                    </p>
                    <div v-else class="mt-3 space-y-3">
                        <div class="grid gap-2">
                            <Button
                                v-for="t in transitions"
                                :key="t.value"
                                :variant="
                                    actionStyles[t.value]
                                        ? 'default'
                                        : 'outline'
                                "
                                :class="[
                                    actionStyles[t.value],
                                    transition.status === t.value
                                        ? 'ring-2 ring-ring ring-offset-2'
                                        : '',
                                ]"
                                :disabled="transition.processing"
                                @click="submitTransition(t.value)"
                            >
                                {{ actionLabels[t.value] ?? t.label }}
                            </Button>
                        </div>

                        <div
                            v-if="
                                [
                                    'info_requested',
                                    'rejected',
                                    'approved',
                                ].includes(transition.status)
                            "
                            class="space-y-2 rounded-lg bg-muted/50 p-3"
                        >
                            <label for="comment" class="text-sm font-medium">
                                {{
                                    needsComment
                                        ? 'Message to applicant (required)'
                                        : 'Note to applicant (optional)'
                                }}
                            </label>
                            <textarea
                                id="comment"
                                v-model="transition.comment"
                                rows="3"
                                class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm"
                                :placeholder="
                                    transition.status === 'info_requested'
                                        ? 'e.g. Please upload a clearer copy of your visa.'
                                        : ''
                                "
                            />
                            <InputError
                                :message="
                                    transition.errors.comment ??
                                    transition.errors.status
                                "
                            />
                            <div class="flex justify-end gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="transition.reset()"
                                    >Cancel</Button
                                >
                                <Button
                                    size="sm"
                                    :disabled="transition.processing"
                                    @click="postTransition"
                                    >Confirm
                                    {{
                                        actionLabels[
                                            transition.status
                                        ]?.toLowerCase()
                                    }}</Button
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="req.certificate_number"
                        class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900"
                    >
                        Certificate <strong>{{ req.certificate_number }}</strong
                        >, valid until {{ formatDate(req.valid_until) }}
                    </div>
                    <div
                        v-if="req.decision_note"
                        class="mt-3 rounded-lg bg-muted p-3 text-sm"
                    >
                        <p class="text-xs text-muted-foreground">
                            Decision note
                        </p>
                        {{ req.decision_note }}
                    </div>
                </section>

                <section class="rounded-xl border p-5">
                    <h2 class="mb-3 font-semibold">Applicant</h2>
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Name</dt>
                            <dd class="font-medium">
                                {{ req.applicant?.name }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Email</dt>
                            <dd>{{ req.applicant?.email }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Phone</dt>
                            <dd>{{ req.applicant?.phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Emirates ID</dt>
                            <dd class="font-mono">
                                {{ req.applicant?.emirates_id ?? '—' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-xl border p-5">
                    <h2 class="mb-4 font-semibold">Fees</h2>
                    <FeeSummary :fees="req.fees" />
                </section>

                <section
                    v-if="req.payments?.length"
                    class="rounded-xl border p-5"
                >
                    <h2 class="mb-3 font-semibold">Payments</h2>
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
                                v-if="payment.authorization_code"
                                class="text-xs text-muted-foreground"
                            >
                                Auth {{ payment.authorization_code }}
                            </p>
                            <p
                                v-if="payment.failure_reason"
                                class="text-xs text-red-600"
                            >
                                {{ payment.failure_reason }}
                            </p>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>
