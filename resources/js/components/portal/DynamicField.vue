<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatMoney } from '@/lib/portal';
import type { FormField } from '@/types';

const props = defineProps<{
    field: FormField;
    error?: string;
}>();

const model = defineModel<unknown>();

const id = computed(() => `field-${props.field.name}`);

// Checkbox groups (with options) hold an array; a lone checkbox is a boolean.
const checkedValues = computed<string[]>(() =>
    Array.isArray(model.value) ? (model.value as string[]) : [],
);

function toggle(value: string, checked: boolean) {
    model.value = checked
        ? [...checkedValues.value, value]
        : checkedValues.value.filter((v) => v !== value);
}

const inputClass =
    'flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive';
</script>

<template>
    <div class="grid gap-2">
        <!-- Single consent-style checkbox -->
        <label
            v-if="field.type === 'checkbox' && !field.options?.length"
            class="flex items-start gap-3 rounded-lg border bg-muted/40 p-3 text-sm"
        >
            <input
                :id="id"
                type="checkbox"
                class="mt-0.5 size-4 accent-primary"
                :checked="Boolean(model)"
                @change="model = ($event.target as HTMLInputElement).checked"
            />
            <span>
                {{ field.label }}
                <span v-if="field.required" class="text-destructive">*</span>
            </span>
        </label>

        <template v-else>
            <Label :for="id">
                {{ field.label }}
                <span v-if="field.required" class="text-destructive">*</span>
            </Label>

            <Select
                v-if="field.type === 'select'"
                :model-value="(model as string) ?? undefined"
                @update:model-value="model = $event"
            >
                <SelectTrigger :id="id" class="w-full" :aria-invalid="!!error">
                    <SelectValue
                        :placeholder="field.placeholder ?? 'Select an option'"
                    />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in field.options"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                        <span
                            v-if="option.fee"
                            class="text-xs text-muted-foreground"
                        >
                            (+{{ formatMoney(option.fee) }})
                        </span>
                    </SelectItem>
                </SelectContent>
            </Select>

            <div
                v-else-if="field.type === 'radio'"
                class="grid gap-2 sm:grid-cols-2"
                role="radiogroup"
                :aria-labelledby="id"
            >
                <label
                    v-for="option in field.options"
                    :key="option.value"
                    class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm transition-colors has-[:checked]:border-primary has-[:checked]:bg-accent"
                >
                    <input
                        type="radio"
                        class="size-4 accent-primary"
                        :name="field.name"
                        :value="option.value"
                        :checked="model === option.value"
                        @change="model = option.value"
                    />
                    <span class="flex-1">{{ option.label }}</span>
                    <span
                        v-if="option.fee"
                        class="text-xs font-medium text-muted-foreground"
                        >+{{ formatMoney(option.fee) }}</span
                    >
                </label>
            </div>

            <div
                v-else-if="field.type === 'checkbox'"
                class="grid gap-2 sm:grid-cols-2"
            >
                <label
                    v-for="option in field.options"
                    :key="option.value"
                    class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm transition-colors has-[:checked]:border-primary has-[:checked]:bg-accent"
                >
                    <input
                        type="checkbox"
                        class="size-4 accent-primary"
                        :value="option.value"
                        :checked="checkedValues.includes(option.value)"
                        @change="
                            toggle(
                                option.value,
                                ($event.target as HTMLInputElement).checked,
                            )
                        "
                    />
                    <span class="flex-1">{{ option.label }}</span>
                    <span
                        v-if="option.fee"
                        class="text-xs font-medium text-muted-foreground"
                        >+{{ formatMoney(option.fee) }}</span
                    >
                </label>
            </div>

            <textarea
                v-else-if="field.type === 'textarea'"
                :id="id"
                rows="3"
                :class="inputClass"
                :placeholder="field.placeholder"
                :aria-invalid="!!error"
                :value="(model as string) ?? ''"
                @input="model = ($event.target as HTMLTextAreaElement).value"
            />

            <Input
                v-else
                :id="id"
                :type="field.type"
                :placeholder="field.placeholder"
                :min="field.min"
                :max="field.max"
                :aria-invalid="!!error"
                :model-value="(model as string | number) ?? ''"
                @update:model-value="model = $event"
            />
        </template>

        <p v-if="field.help" class="text-xs text-muted-foreground">
            {{ field.help }}
        </p>
        <InputError :message="error" />
    </div>
</template>
