<script setup lang="ts">
import { Briefcase, Car, FileText, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    icon: string;
}>();

const icons = {
    briefcase: Briefcase,
    car: Car,
    'shield-check': ShieldCheck,
    'file-text': FileText,
} as const;

const component = computed(
    () => icons[props.icon as keyof typeof icons] ?? FileText,
);
</script>

<template>
    <component :is="component" />
</template>
