<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import Pagination from '@/components/portal/Pagination.vue';
import StatusBadge from '@/components/portal/StatusBadge.vue';
import { Input } from '@/components/ui/input';
import { formatDate, formatMoney } from '@/lib/portal';
import admin from '@/routes/admin';
import type { Paginated, ServiceRequest, Status } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Back office', href: admin.dashboard() },
            { title: 'Requests', href: admin.requests.index() },
        ],
    },
});

const props = defineProps<{
    requests: Paginated<ServiceRequest>;
    statuses: Status[];
    services: { name: string; slug: string }[];
    filters: { search?: string; status?: string; service?: string };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const service = ref(props.filters.service ?? '');

let timer: ReturnType<typeof setTimeout> | undefined;

function apply() {
    const query = Object.fromEntries(
        Object.entries({
            search: search.value,
            status: status.value,
            service: service.value,
        }).filter(([, v]) => v),
    );

    router.get(
        admin.requests.index.url({ query }),
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
}

watch([status, service], apply);
watch(search, () => {
    clearTimeout(timer);
    timer = setTimeout(apply, 350);
});
</script>

<template>
    <Head title="Requests" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Requests</h1>
            <p class="text-sm text-muted-foreground">
                {{ requests.meta.total }} request{{
                    requests.meta.total === 1 ? '' : 's'
                }}
                match your filters.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <div class="relative min-w-64 flex-1">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Search reference, applicant name or email"
                />
            </div>
            <select
                v-model="status"
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                aria-label="Filter by status"
            >
                <option value="">All statuses</option>
                <option v-for="s in statuses" :key="s.value" :value="s.value">
                    {{ s.label }}
                </option>
            </select>
            <select
                v-model="service"
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                aria-label="Filter by service"
            >
                <option value="">All services</option>
                <option v-for="s in services" :key="s.slug" :value="s.slug">
                    {{ s.name }}
                </option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium">Applicant</th>
                        <th class="px-4 py-3 font-medium">Service</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Assigned</th>
                        <th class="px-4 py-3 font-medium">Created</th>
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
                                :href="admin.requests.show(item.reference)"
                                class="text-primary hover:underline"
                                >{{ item.reference }}</Link
                            >
                            <span
                                v-if="item.channel === 'api'"
                                class="ml-1 rounded bg-muted px-1 text-[10px] text-muted-foreground uppercase"
                                >api</span
                            >
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ item.applicant?.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ item.applicant?.email }}
                            </p>
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
                            {{ item.assignee?.name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ formatDate(item.created_at) }}
                        </td>
                    </tr>
                    <tr v-if="!requests.data.length">
                        <td
                            colspan="7"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No requests found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :meta="requests.meta" />
    </div>
</template>
