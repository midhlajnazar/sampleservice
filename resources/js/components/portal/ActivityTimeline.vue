<script setup lang="ts">
import { Lock } from '@lucide/vue';
import StatusBadge from '@/components/portal/StatusBadge.vue';
import { formatDate } from '@/lib/portal';
import type { Activity } from '@/types';

defineProps<{
    activities: Activity[];
}>();
</script>

<template>
    <ol class="relative space-y-5 border-l border-border pl-5">
        <li v-for="activity in activities" :key="activity.id" class="relative">
            <span
                class="absolute top-1.5 -left-[25px] size-2.5 rounded-full ring-4 ring-background"
                :class="
                    activity.is_internal
                        ? 'bg-amber-400'
                        : activity.actor_is_admin
                          ? 'bg-primary'
                          : 'bg-neutral-400'
                "
            />
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="font-medium">{{ activity.actor }}</span>
                <span class="text-muted-foreground">{{
                    activity.action.replaceAll('_', ' ')
                }}</span>
                <StatusBadge
                    v-if="activity.to_status"
                    :value="activity.to_status.value"
                    :label="activity.to_status.label"
                />
                <span
                    v-if="activity.is_internal"
                    class="inline-flex items-center gap-1 text-xs text-amber-700"
                >
                    <Lock class="size-3" /> internal
                </span>
            </div>
            <p
                v-if="activity.comment"
                class="mt-1 text-sm whitespace-pre-line text-foreground/80"
            >
                {{ activity.comment }}
            </p>
            <time class="text-xs text-muted-foreground">{{
                formatDate(activity.created_at, true)
            }}</time>
        </li>
    </ol>
</template>
