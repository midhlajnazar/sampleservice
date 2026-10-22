<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';
import type { Service } from '@/types';

const props = defineProps<{
    service: Service;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Back office', href: admin.dashboard() },
        { title: 'Services', href: admin.services.index() },
        { title: props.service.name, href: '#' },
    ],
});

const form = useForm({
    name: props.service.name,
    category: props.service.category,
    summary: props.service.summary,
    description: props.service.description ?? '',
    base_fee: props.service.base_fee,
    vat_rate: props.service.vat_rate,
    processing_days: props.service.processing_days,
    is_active: props.service.is_active,
    form_schema: JSON.stringify(props.service.form_schema, null, 2),
    document_schema: JSON.stringify(props.service.document_schema, null, 2),
});

function save() {
    form.put(admin.services.update.url(props.service.slug));
}

const textareaClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs';
</script>

<template>
    <Head :title="`Edit ${service.name}`" />

    <form class="flex flex-1 flex-col gap-6 p-4 md:p-6" @submit.prevent="save">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold tracking-tight">
                Edit {{ service.name }}
            </h1>
            <Button :disabled="form.processing">Save changes</Button>
        </div>

        <section class="grid gap-5 rounded-xl border p-5 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="category">Category</Label>
                <Input id="category" v-model="form.category" />
                <InputError :message="form.errors.category" />
            </div>
            <div class="grid gap-2 md:col-span-2">
                <Label for="summary">Summary</Label>
                <Input id="summary" v-model="form.summary" />
                <InputError :message="form.errors.summary" />
            </div>
            <div class="grid gap-2 md:col-span-2">
                <Label for="description">Description</Label>
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    :class="textareaClass"
                />
                <InputError :message="form.errors.description" />
            </div>
            <div class="grid gap-2">
                <Label for="base_fee">Base fee (AED)</Label>
                <Input
                    id="base_fee"
                    v-model="form.base_fee"
                    type="number"
                    step="0.01"
                    min="0"
                />
                <InputError :message="form.errors.base_fee" />
            </div>
            <div class="grid gap-2">
                <Label for="vat_rate">VAT rate (%)</Label>
                <Input
                    id="vat_rate"
                    v-model="form.vat_rate"
                    type="number"
                    step="0.01"
                    min="0"
                />
                <InputError :message="form.errors.vat_rate" />
            </div>
            <div class="grid gap-2">
                <Label for="processing_days">Processing days</Label>
                <Input
                    id="processing_days"
                    v-model="form.processing_days"
                    type="number"
                    min="1"
                />
                <InputError :message="form.errors.processing_days" />
            </div>
            <label class="flex items-center gap-3 self-end pb-2 text-sm">
                <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="size-4 accent-primary"
                />
                Service is live and accepting applications
            </label>
        </section>

        <section class="grid gap-5 rounded-xl border p-5 lg:grid-cols-2">
            <div class="grid gap-2">
                <Label for="form_schema">Form schema (JSON)</Label>
                <p class="text-xs text-muted-foreground">
                    Sections of fields. Types: text, email, tel, number, date,
                    textarea, select, radio, checkbox. Options can carry a
                    <code>fee</code>; fields can use <code>show_if</code>.
                </p>
                <textarea
                    id="form_schema"
                    v-model="form.form_schema"
                    rows="24"
                    spellcheck="false"
                    :class="[textareaClass, 'font-mono text-xs']"
                />
                <InputError :message="form.errors.form_schema" />
            </div>
            <div class="grid gap-2">
                <Label for="document_schema">Documents schema (JSON)</Label>
                <p class="text-xs text-muted-foreground">
                    Each document: key, label, required, mimes, max_kb, and an
                    optional description / show_if.
                </p>
                <textarea
                    id="document_schema"
                    v-model="form.document_schema"
                    rows="24"
                    spellcheck="false"
                    :class="[textareaClass, 'font-mono text-xs']"
                />
                <InputError :message="form.errors.document_schema" />
            </div>
        </section>
    </form>
</template>
