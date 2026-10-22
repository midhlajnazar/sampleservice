<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <path
            d="M12 1.5 2 6.5V9h20V6.5L12 1.5ZM4 10.5v7.5h3v-7.5H4Zm6.5 0v7.5h3v-7.5h-3Zm6.5 0v7.5h3v-7.5h-3ZM2 19.5V22h20v-2.5H2Z"
        />
    </svg>
</template>
