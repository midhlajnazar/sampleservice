<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { CheckCircle2, Clock, FileText, Wallet } from '@lucide/vue';
import ServiceIcon from '@/components/portal/ServiceIcon.vue';
import { Button } from '@/components/ui/button';
import { formatBytes, formatMoney } from '@/lib/portal';
import { create as applyRoute } from '@/routes/requests';
import { index as servicesIndex } from '@/routes/services';
import type { Service } from '@/types';

const props = defineProps<{
    service: Service;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Services', href: servicesIndex() },
        { title: props.service.name, href: '#' },
    ],
});

const optionalFees = (props.service.form_schema ?? []).flatMap((section) =>
    section.fields.flatMap((field) =>
        (field.options ?? [])
            .filter((o) => o.fee)
            .map((o) => ({
                label: `${field.label}: ${o.label}`,
                fee: o.fee as number,
            })),
    ),
);
</script>

<template>
    <Head :title="service.name" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start gap-4">
            <span
                class="flex size-12 items-center justify-center rounded-xl bg-accent text-primary [&>svg]:size-6"
            >
                <ServiceIcon :icon="service.icon" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-muted-foreground">
                    {{ service.category }}
                </p>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ service.name }}
                </h1>
                <p class="mt-1 max-w-2xl text-muted-foreground">
                    {{ service.summary }}
                </p>
            </div>
            <Button as-child size="lg">
                <Link :href="applyRoute(service.slug)">Apply now</Link>
            </Button>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-xl border p-5">
                    <h2 class="font-semibold">About this service</h2>
                    <p class="mt-2 text-sm leading-relaxed text-foreground/80">
                        {{ service.description }}
                    </p>
                    <h3 class="mt-5 text-sm font-semibold">Eligibility</h3>
                    <ul class="mt-2 space-y-1.5">
                        <li
                            v-for="rule in service.eligibility"
                            :key="rule"
                            class="flex items-start gap-2 text-sm"
                        >
                            <CheckCircle2
                                class="mt-0.5 size-4 shrink-0 text-primary"
                            />
                            {{ rule }}
                        </li>
                    </ul>
                </section>

                <section class="rounded-xl border p-5">
                    <h2 class="font-semibold">Required documents</h2>
                    <p class="text-sm text-muted-foreground">
                        Some documents are only needed depending on your
                        answers.
                    </p>
                    <ul class="mt-4 divide-y">
                        <li
                            v-for="doc in service.document_schema"
                            :key="doc.key"
                            class="flex items-start gap-3 py-3"
                        >
                            <FileText
                                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            />
                            <div class="flex-1 text-sm">
                                <p class="font-medium">
                                    {{ doc.label }}
                                    <span
                                        v-if="doc.show_if"
                                        class="ml-1 rounded bg-muted px-1.5 py-0.5 text-xs font-normal text-muted-foreground"
                                        >if applicable</span
                                    >
                                    <span
                                        v-else-if="!doc.required"
                                        class="ml-1 rounded bg-muted px-1.5 py-0.5 text-xs font-normal text-muted-foreground"
                                        >optional</span
                                    >
                                </p>
                                <p
                                    v-if="doc.description"
                                    class="text-muted-foreground"
                                >
                                    {{ doc.description }}
                                </p>
                            </div>
                            <span
                                class="text-xs text-muted-foreground uppercase"
                                >{{ doc.mimes.join(', ') }} ·
                                {{ formatBytes(doc.max_kb * 1024) }}</span
                            >
                        </li>
                    </ul>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="rounded-xl border p-5">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <Wallet class="size-4" /> Fees
                    </h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt>Service fee</dt>
                            <dd class="font-medium">
                                {{ formatMoney(service.base_fee) }}
                            </dd>
                        </div>
                        <div
                            v-for="fee in optionalFees"
                            :key="fee.label"
                            class="flex justify-between gap-3 text-muted-foreground"
                        >
                            <dt>{{ fee.label }}</dt>
                            <dd class="whitespace-nowrap">
                                +{{ formatMoney(fee.fee) }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between border-t pt-2 text-muted-foreground"
                        >
                            <dt>VAT</dt>
                            <dd>{{ service.vat_rate }}%</dd>
                        </div>
                    </dl>
                </section>
                <section class="rounded-xl border p-5">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <Clock class="size-4" /> Processing time
                    </h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Usually {{ service.processing_days }} working day{{
                            service.processing_days > 1 ? 's' : ''
                        }}
                        after payment.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</template>
