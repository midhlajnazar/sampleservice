<script setup lang="ts">
import { statusClasses } from '@/lib/portal';

defineProps<{
    value: string;
    label?: string;
}>();
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset"
        :class="statusClasses(value)"
    >
        {{ label ?? value.replaceAll('_', ' ') }}
    </span>
</template>
