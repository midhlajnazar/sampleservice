<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import StatusBadge from '@/components/portal/StatusBadge.vue';
import { formatDate, formatMoney, statusClasses } from '@/lib/portal';
import admin from '@/routes/admin';
import type { ServiceRequest, Status } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Back office', href: admin.dashboard() }],
    },
});

const props = defineProps<{
    stats: {
        queue: number;
        under_review: number;
        info_requested: number;
        approved: number;
        revenue: number;
        refunded: number;
        applicants: number;
    };
    byStatus: (Status & { total: number })[];
    byService: { name: string; total: number }[];
    needsAttention: ServiceRequest[];
}>();

const maxService = Math.max(1, ...props.byService.map((s) => s.total));
const totalRequests = props.byStatus.reduce((sum, s) => sum + s.total, 0);
</script>

<template>
    <Head title="Back office" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Back office</h1>
            <p class="text-sm text-muted-foreground">
                Review and process applications.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Link
                :href="admin.requests.index({ query: { status: 'submitted' } })"
                class="rounded-xl border p-4 hover:border-primary"
            >
                <p class="text-sm text-muted-foreground">New in queue</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">
                    {{ stats.queue }}
                </p>
            </Link>
            <Link
                :href="
                    admin.requests.index({ query: { status: 'under_review' } })
                "
                class="rounded-xl border p-4 hover:border-primary"
            >
                <p class="text-sm text-muted-foreground">Under review</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">
                    {{ stats.under_review }}
                </p>
            </Link>
            <Link
                :href="
                    admin.requests.index({
                        query: { status: 'info_requested' },
                    })
                "
                class="rounded-xl border p-4 hover:border-primary"
            >
                <p class="text-sm text-muted-foreground">
                    Waiting on applicant
                </p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">
                    {{ stats.info_requested }}
                </p>
            </Link>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Revenue collected</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ formatMoney(stats.revenue) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ formatMoney(stats.refunded) }} refunded
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-xl border lg:col-span-2">
                <div class="flex items-center justify-between border-b p-4">
                    <h2 class="font-semibold">Oldest open requests</h2>
                    <Link
                        :href="admin.requests.index()"
                        class="text-sm text-primary hover:underline"
                        >All requests</Link
                    >
                </div>
                <ul v-if="needsAttention.length" class="divide-y">
                    <li v-for="item in needsAttention" :key="item.id">
                        <Link
                            :href="admin.requests.show(item.reference)"
                            class="flex flex-wrap items-center gap-3 p-4 hover:bg-muted/30"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">
                                    {{ item.service?.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ item.reference }} ·
                                    {{ item.applicant?.name }} · submitted
                                    {{ formatDate(item.submitted_at, true) }}
                                </p>
                            </div>
                            <StatusBadge
                                :value="item.status.value"
                                :label="item.status.label"
                            />
                        </Link>
                    </li>
                </ul>
                <p v-else class="p-8 text-center text-sm text-muted-foreground">
                    Nothing waiting. Nice work.
                </p>
            </section>

            <div class="space-y-6">
                <section class="rounded-xl border p-4">
                    <h2 class="mb-3 font-semibold">By status</h2>
                    <div
                        class="mb-4 flex h-2.5 overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            v-for="s in byStatus.filter((s) => s.total)"
                            :key="s.value"
                            :class="statusClasses(s.value)"
                            class="ring-1 ring-inset"
                            :style="{
                                width: `${(s.total / totalRequests) * 100}%`,
                            }"
                            :title="`${s.label}: ${s.total}`"
                        />
                    </div>
                    <ul class="space-y-1.5 text-sm">
                        <li
                            v-for="s in byStatus"
                            :key="s.value"
                            class="flex items-center justify-between"
                        >
                            <Link
                                :href="
                                    admin.requests.index({
                                        query: { status: s.value },
                                    })
                                "
                                class="hover:underline"
                                ><StatusBadge :value="s.value" :label="s.label"
                            /></Link>
                            <span class="tabular-nums">{{ s.total }}</span>
                        </li>
                    </ul>
                </section>

                <section class="rounded-xl border p-4">
                    <h2 class="mb-3 font-semibold">By service</h2>
                    <ul class="space-y-3 text-sm">
                        <li v-for="s in byService" :key="s.name">
                            <div class="flex justify-between">
                                <span>{{ s.name }}</span>
                                <span class="tabular-nums">{{ s.total }}</span>
                            </div>
                            <div class="mt-1 h-1.5 rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full bg-primary"
                                    :style="{
                                        width: `${(s.total / maxService) * 100}%`,
                                    }"
                                />
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</template>
