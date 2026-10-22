<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Check, ChevronLeft, ChevronRight, FileText } from '@lucide/vue';
import { computed, ref } from 'vue';
import DynamicField from '@/components/portal/DynamicField.vue';
import FeeSummary from '@/components/portal/FeeSummary.vue';
import FileUpload from '@/components/portal/FileUpload.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { formatBytes, isVisible } from '@/lib/portal';
import { store } from '@/routes/requests';
import { index as servicesIndex, show as showService } from '@/routes/services';
import type { FeeBreakdown, FormField, Service } from '@/types';

const props = defineProps<{
    service: Service;
    defaults: Record<string, string>;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Services', href: servicesIndex() },
        { title: props.service.name, href: showService(props.service.slug) },
        { title: 'Apply', href: '#' },
    ],
});

const sections = props.service.form_schema ?? [];
const documentSchema = props.service.document_schema ?? [];

type FieldValue = string | number | boolean | string[];

// Only pre-fill defaults for fields this service actually has.
const initialFields: Record<string, FieldValue> = {};
for (const section of sections) {
    for (const field of section.fields) {
        initialFields[field.name] =
            props.defaults[field.name] ??
            (field.type === 'checkbox'
                ? field.options?.length
                    ? []
                    : false
                : '');
    }
}

const form = useForm({
    fields: initialFields,
    documents: {} as Record<string, File | null>,
});

const errors = computed(
    () => form.errors as Record<string, string | undefined>,
);

const steps = [...sections.map((s) => s.title), 'Documents', 'Review & pay'];
const step = ref(0);
const isDocumentsStep = computed(() => step.value === sections.length);
const isReviewStep = computed(() => step.value === sections.length + 1);

const visibleFields = (fields: FormField[]) =>
    fields.filter((f) => isVisible(f.show_if, form.fields));

const visibleDocuments = computed(() =>
    documentSchema.filter((d) => isVisible(d.show_if, form.fields)),
);

/** Client-side mirror of App\Services\FeeCalculator. */
const fees = computed<FeeBreakdown>(() => {
    const breakdown = [
        { label: `${props.service.name} fee`, amount: props.service.base_fee },
    ];

    for (const section of sections) {
        for (const field of visibleFields(section.fields)) {
            const picked = ([] as unknown[]).concat(
                form.fields[field.name] ?? [],
            );

            for (const option of field.options ?? []) {
                if (option.fee && picked.includes(option.value)) {
                    breakdown.push({
                        label: `${field.label}: ${option.label}`,
                        amount: option.fee,
                    });
                }
            }
        }
    }

    const subtotal = breakdown.reduce((sum, line) => sum + line.amount, 0);
    const vat = Math.round(subtotal * props.service.vat_rate) / 100;

    return { breakdown, subtotal, vat, total: subtotal + vat, currency: 'AED' };
});

function isEmpty(value: unknown): boolean {
    return (
        value === null ||
        value === undefined ||
        value === '' ||
        value === false ||
        (Array.isArray(value) && value.length === 0)
    );
}

/** Light client-side check so people don't reach the end with gaps. */
function validateStep(index: number): boolean {
    form.clearErrors();
    const found: Record<string, string> = {};

    if (index < sections.length) {
        for (const field of visibleFields(sections[index].fields)) {
            const value = form.fields[field.name];

            if (field.required && isEmpty(value)) {
                found[`fields.${field.name}`] =
                    field.type === 'checkbox' && !field.options?.length
                        ? 'Please confirm to continue.'
                        : `${field.label} is required.`;
            } else if (
                field.pattern &&
                typeof value === 'string' &&
                value !== '' &&
                !new RegExp(field.pattern).test(value)
            ) {
                found[`fields.${field.name}`] =
                    `${field.label} format is invalid.`;
            }
        }
    } else if (index === sections.length) {
        for (const doc of visibleDocuments.value) {
            if (doc.required && !form.documents[doc.key]) {
                found[`documents.${doc.key}`] = `${doc.label} is required.`;
            }
        }
    }

    form.setError(found as never);

    return Object.keys(found).length === 0;
}

function next() {
    if (validateStep(step.value)) {
        step.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function goTo(index: number) {
    if (index < step.value) {
        step.value = index;
    }
}

/** After a server-side validation failure, jump to the first step with an error. */
function stepForErrors(serverErrors: Record<string, string>): number {
    const keys = Object.keys(serverErrors);

    for (const [i, section] of sections.entries()) {
        if (
            section.fields.some((f) =>
                keys.some((k) => k.startsWith(`fields.${f.name}`)),
            )
        ) {
            return i;
        }
    }

    return keys.some((k) => k.startsWith('documents'))
        ? sections.length
        : step.value;
}

function submit() {
    form.transform((data) => {
        // Drop answers for hidden fields and documents that no longer apply.
        const fields: Record<string, FieldValue> = {};
        for (const section of sections) {
            for (const field of visibleFields(section.fields)) {
                fields[field.name] = data.fields[field.name];
            }
        }

        const documents: Record<string, File> = {};
        for (const doc of visibleDocuments.value) {
            const file = data.documents[doc.key];
            if (file) {
                documents[doc.key] = file;
            }
        }

        return { fields, documents };
    }).post(store.url(props.service.slug), {
        forceFormData: true,
        onError: (serverErrors) => {
            step.value = stepForErrors(serverErrors);
        },
    });
}
</script>

<template>
    <Head :title="`Apply: ${service.name}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <p class="text-sm text-muted-foreground">New application</p>
            <h1 class="text-2xl font-semibold tracking-tight">
                {{ service.name }}
            </h1>
        </div>

        <!-- Stepper -->
        <ol class="flex flex-wrap gap-2">
            <li v-for="(title, i) in steps" :key="title">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm transition-colors"
                    :class="[
                        i === step
                            ? 'border-primary bg-primary text-primary-foreground'
                            : i < step
                              ? 'border-primary/40 bg-accent text-accent-foreground hover:border-primary'
                              : 'text-muted-foreground',
                        i < step ? 'cursor-pointer' : 'cursor-default',
                    ]"
                    @click="goTo(i)"
                >
                    <span
                        class="flex size-5 items-center justify-center rounded-full text-xs"
                        :class="i === step ? 'bg-white/20' : 'bg-muted'"
                    >
                        <Check v-if="i < step" class="size-3" />
                        <template v-else>{{ i + 1 }}</template>
                    </span>
                    {{ title }}
                </button>
            </li>
        </ol>

        <div class="grid gap-6 lg:grid-cols-3">
            <form class="space-y-6 lg:col-span-2" @submit.prevent="submit">
                <!-- Form sections -->
                <template v-for="(section, i) in sections" :key="section.title">
                    <section v-if="step === i" class="rounded-xl border p-5">
                        <h2 class="mb-5 text-lg font-semibold">
                            {{ section.title }}
                        </h2>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <DynamicField
                                v-for="field in visibleFields(section.fields)"
                                :key="field.name"
                                v-model="form.fields[field.name]"
                                :field="field"
                                :error="errors[`fields.${field.name}`]"
                                :class="
                                    ['textarea', 'radio', 'checkbox'].includes(
                                        field.type,
                                    )
                                        ? 'sm:col-span-2'
                                        : ''
                                "
                            />
                        </div>
                    </section>
                </template>

                <!-- Documents -->
                <section v-if="isDocumentsStep" class="rounded-xl border p-5">
                    <h2 class="text-lg font-semibold">Upload documents</h2>
                    <p class="mb-5 text-sm text-muted-foreground">
                        Clear scans or photos. The list depends on your earlier
                        answers.
                    </p>
                    <div class="grid gap-5">
                        <FileUpload
                            v-for="doc in visibleDocuments"
                            :key="doc.key"
                            v-model="form.documents[doc.key]"
                            :requirement="doc"
                            :error="errors[`documents.${doc.key}`]"
                        />
                    </div>
                </section>

                <!-- Review -->
                <section
                    v-if="isReviewStep"
                    class="space-y-5 rounded-xl border p-5"
                >
                    <h2 class="text-lg font-semibold">
                        Review your application
                    </h2>

                    <div v-for="(section, i) in sections" :key="section.title">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-semibold">
                                {{ section.title }}
                            </h3>
                            <button
                                type="button"
                                class="text-sm text-primary hover:underline"
                                @click="step = i"
                            >
                                Edit
                            </button>
                        </div>
                        <dl
                            class="grid gap-x-6 gap-y-2 rounded-lg bg-muted/40 p-3 text-sm sm:grid-cols-2"
                        >
                            <template
                                v-for="field in visibleFields(section.fields)"
                                :key="field.name"
                            >
                                <div
                                    v-if="
                                        !(
                                            field.type === 'checkbox' &&
                                            !field.options?.length
                                        )
                                    "
                                >
                                    <dt class="text-muted-foreground">
                                        {{ field.label }}
                                    </dt>
                                    <dd class="font-medium break-words">
                                        {{
                                            ([] as unknown[])
                                                .concat(
                                                    form.fields[field.name] ??
                                                        [],
                                                )
                                                .map(
                                                    (v) =>
                                                        field.options?.find(
                                                            (o) =>
                                                                o.value === v,
                                                        )?.label ?? v,
                                                )
                                                .join(', ') || '—'
                                        }}
                                    </dd>
                                </div>
                            </template>
                        </dl>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-semibold">Documents</h3>
                            <button
                                type="button"
                                class="text-sm text-primary hover:underline"
                                @click="step = sections.length"
                            >
                                Edit
                            </button>
                        </div>
                        <ul class="divide-y rounded-lg bg-muted/40 text-sm">
                            <li
                                v-for="doc in visibleDocuments"
                                :key="doc.key"
                                class="flex items-center gap-3 px-3 py-2"
                            >
                                <FileText
                                    class="size-4 text-muted-foreground"
                                />
                                <span class="flex-1">{{ doc.label }}</span>
                                <span
                                    v-if="form.documents[doc.key]"
                                    class="max-w-48 truncate text-muted-foreground"
                                    >{{ form.documents[doc.key]?.name }} ({{
                                        formatBytes(
                                            form.documents[doc.key]?.size ?? 0,
                                        )
                                    }})</span
                                >
                                <span v-else class="text-muted-foreground"
                                    >Not provided</span
                                >
                            </li>
                        </ul>
                    </div>

                    <p
                        v-if="Object.keys(errors).length"
                        class="rounded-lg bg-red-50 p-3 text-sm text-red-700"
                    >
                        Please fix the highlighted fields.
                    </p>
                </section>

                <div class="flex items-center justify-between">
                    <Button
                        v-if="step > 0"
                        type="button"
                        variant="outline"
                        @click="step--"
                    >
                        <ChevronLeft class="size-4" /> Back
                    </Button>
                    <span v-else />

                    <Button v-if="!isReviewStep" type="button" @click="next">
                        Continue <ChevronRight class="size-4" />
                    </Button>
                    <Button v-else type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        Submit and continue to payment
                    </Button>
                </div>

                <div
                    v-if="form.progress"
                    class="h-1.5 overflow-hidden rounded-full bg-muted"
                >
                    <div
                        class="h-full bg-primary transition-all"
                        :style="{ width: `${form.progress.percentage}%` }"
                    />
                </div>
            </form>

            <aside class="lg:sticky lg:top-4 lg:self-start">
                <section class="rounded-xl border p-5">
                    <h2 class="mb-4 font-semibold">Fee summary</h2>
                    <FeeSummary :fees="fees" :vat-rate="service.vat_rate" />
                    <p class="mt-4 text-xs text-muted-foreground">
                        Updates as you choose options. You pay after reviewing
                        your application.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</template>
