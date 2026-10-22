<script setup lang="ts">
import { CheckCircle2, FileUp, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { formatBytes } from '@/lib/portal';
import type { DocumentRequirement } from '@/types';

const props = defineProps<{
    requirement: DocumentRequirement;
    error?: string;
    optional?: boolean;
}>();

const model = defineModel<File | null>({ default: null });

const input = ref<HTMLInputElement>();
const dragging = ref(false);
const localError = ref<string>();

const accept = computed(() =>
    props.requirement.mimes.map((m) => `.${m}`).join(','),
);

function pick(file: File | undefined) {
    localError.value = undefined;

    if (!file) {
        return;
    }

    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

    if (!props.requirement.mimes.includes(extension)) {
        localError.value = `Allowed types: ${props.requirement.mimes.join(', ').toUpperCase()}`;

        return;
    }

    if (file.size > props.requirement.max_kb * 1024) {
        localError.value = `File is larger than ${formatBytes(props.requirement.max_kb * 1024)}.`;

        return;
    }

    model.value = file;
}

function onDrop(event: DragEvent) {
    dragging.value = false;
    pick(event.dataTransfer?.files[0]);
}

function clear() {
    model.value = null;

    if (input.value) {
        input.value.value = '';
    }
}
</script>

<template>
    <div class="grid gap-1.5">
        <div class="flex items-baseline justify-between gap-2">
            <p class="text-sm font-medium">
                {{ requirement.label }}
                <span
                    v-if="requirement.required && !optional"
                    class="text-destructive"
                    >*</span
                >
                <span v-else class="text-xs font-normal text-muted-foreground"
                    >(optional)</span
                >
            </p>
            <p class="text-xs text-muted-foreground uppercase">
                {{ requirement.mimes.join(', ') }} · max
                {{ formatBytes(requirement.max_kb * 1024) }}
            </p>
        </div>
        <p v-if="requirement.description" class="text-xs text-muted-foreground">
            {{ requirement.description }}
        </p>

        <div
            v-if="model"
            class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm"
        >
            <CheckCircle2 class="size-4 shrink-0 text-emerald-600" />
            <span class="min-w-0 flex-1 truncate">{{ model.name }}</span>
            <span class="text-xs text-muted-foreground">{{
                formatBytes(model.size)
            }}</span>
            <button
                type="button"
                class="rounded p-1 text-muted-foreground hover:bg-emerald-100 hover:text-foreground"
                :aria-label="`Remove ${requirement.label}`"
                @click="clear"
            >
                <X class="size-4" />
            </button>
        </div>

        <button
            v-else
            type="button"
            class="flex flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed px-4 py-5 text-sm transition-colors hover:border-primary hover:bg-accent"
            :class="[
                dragging ? 'border-primary bg-accent' : '',
                error || localError ? 'border-destructive/60' : '',
            ]"
            @click="input?.click()"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <FileUp class="size-5 text-muted-foreground" />
            <span
                ><span class="font-medium text-primary">Choose a file</span> or
                drag it here</span
            >
        </button>

        <input
            ref="input"
            type="file"
            class="hidden"
            :accept="accept"
            @change="pick(($event.target as HTMLInputElement).files?.[0])"
        />
        <InputError :message="localError ?? error" />
    </div>
</template>
