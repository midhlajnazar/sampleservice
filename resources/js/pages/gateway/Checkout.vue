<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Lock, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import FeeSummary from '@/components/portal/FeeSummary.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney } from '@/lib/portal';
import { process } from '@/routes/payments';
import { show as showRequest } from '@/routes/requests';
import type { ServiceRequest } from '@/types';

const props = defineProps<{
    serviceRequest: ServiceRequest;
    testCards: { number: string; brand: string; outcome: string }[];
}>();

const form = useForm({
    card_holder: '',
    card_number: '',
    expiry: '',
    cvv: '',
});

const brand = computed(() => {
    const n = form.card_number.replace(/\D/g, '');

    if (n.startsWith('4')) {
        return 'VISA';
    }

    if (/^5[1-5]/.test(n)) {
        return 'MASTERCARD';
    }

    return '';
});

function formatCardNumber(value: string) {
    form.card_number = value
        .replace(/\D/g, '')
        .slice(0, 19)
        .replace(/(.{4})/g, '$1 ')
        .trim();
}

function formatExpiry(value: string) {
    const digits = value.replace(/\D/g, '').slice(0, 4);
    form.expiry =
        digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
}

function useTestCard(number: string) {
    const next = new Date();
    next.setFullYear(next.getFullYear() + 2);

    form.card_number = number;
    form.card_holder ||= 'TEST CARDHOLDER';
    form.expiry = `12/${String(next.getFullYear()).slice(2)}`;
    form.cvv = '123';
    form.clearErrors();
}

const failedAttempts = computed(
    () =>
        (props.serviceRequest.payments ?? []).filter(
            (p) => p.status === 'failed',
        ).length,
);

function pay() {
    form.post(process.url(props.serviceRequest.reference), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="SamplePay checkout" />

    <div class="min-h-screen bg-slate-100 text-slate-900">
        <header class="bg-slate-900 text-white">
            <div
                class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3"
            >
                <span class="flex items-center gap-2 text-lg font-semibold">
                    <span
                        class="flex size-7 items-center justify-center rounded bg-indigo-500 text-sm font-bold"
                        >S</span
                    >
                    SamplePay
                </span>
                <span class="flex items-center gap-1.5 text-xs text-slate-300">
                    <Lock class="size-3.5" /> Secure test checkout
                </span>
            </div>
        </header>

        <div
            class="mx-auto mt-4 max-w-4xl px-4 text-xs font-medium text-amber-800"
        >
            <p class="rounded-md bg-amber-100 px-3 py-2 ring-1 ring-amber-300">
                TEST MODE: this is a dummy payment gateway. No real money is
                charged; use one of the test cards below.
            </p>
        </div>

        <main class="mx-auto grid max-w-4xl gap-6 px-4 py-6 md:grid-cols-5">
            <section
                class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 md:col-span-3"
            >
                <h1 class="text-lg font-semibold">Pay with card</h1>
                <p class="text-sm text-slate-500">
                    Merchant: {{ $page.props.name }} ·
                    {{ serviceRequest.reference }}
                </p>

                <form class="mt-6 space-y-4" @submit.prevent="pay">
                    <div class="grid gap-2">
                        <Label for="card_holder">Name on card</Label>
                        <Input
                            id="card_holder"
                            v-model="form.card_holder"
                            autocomplete="off"
                            placeholder="As printed on the card"
                        />
                        <InputError :message="form.errors.card_holder" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="card_number">Card number</Label>
                        <div class="relative">
                            <Input
                                id="card_number"
                                :model-value="form.card_number"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="4242 4242 4242 4242"
                                class="pr-24 font-mono"
                                @update:model-value="
                                    formatCardNumber(String($event))
                                "
                            />
                            <span
                                v-if="brand"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-xs font-bold tracking-wider text-indigo-600"
                                >{{ brand }}</span
                            >
                        </div>
                        <InputError :message="form.errors.card_number" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="expiry">Expiry</Label>
                            <Input
                                id="expiry"
                                :model-value="form.expiry"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="MM/YY"
                                class="font-mono"
                                @update:model-value="
                                    formatExpiry(String($event))
                                "
                            />
                            <InputError :message="form.errors.expiry" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="cvv">CVV</Label>
                            <Input
                                id="cvv"
                                v-model="form.cvv"
                                inputmode="numeric"
                                autocomplete="off"
                                maxlength="4"
                                placeholder="123"
                                class="font-mono"
                            />
                            <InputError :message="form.errors.cvv" />
                        </div>
                    </div>

                    <Button
                        type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700"
                        size="lg"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" />
                        Pay {{ formatMoney(serviceRequest.fees.total) }}
                    </Button>
                    <p
                        v-if="failedAttempts"
                        class="text-center text-xs text-slate-500"
                    >
                        {{ failedAttempts }} previous attempt{{
                            failedAttempts > 1 ? 's' : ''
                        }}
                        declined.
                    </p>
                </form>

                <div class="mt-6 border-t pt-4">
                    <h2 class="text-sm font-semibold">Test cards</h2>
                    <p class="text-xs text-slate-500">
                        Click to fill. Any future expiry and any CVV work.
                    </p>
                    <ul class="mt-3 grid gap-2">
                        <li v-for="card in testCards" :key="card.number">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-md border px-3 py-2 text-left text-sm hover:border-indigo-400 hover:bg-indigo-50"
                                @click="useTestCard(card.number)"
                            >
                                <span class="font-mono">{{ card.number }}</span>
                                <span
                                    class="text-xs font-medium"
                                    :class="
                                        card.outcome === 'Approved'
                                            ? 'text-emerald-700'
                                            : 'text-red-600'
                                    "
                                    >{{ card.brand }} · {{ card.outcome }}</span
                                >
                            </button>
                        </li>
                    </ul>
                </div>
            </section>

            <aside class="space-y-4 md:col-span-2">
                <section
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                >
                    <h2 class="font-semibold">Order summary</h2>
                    <p class="mb-4 text-sm text-slate-500">
                        {{ serviceRequest.service?.name }}
                    </p>
                    <FeeSummary :fees="serviceRequest.fees" />
                </section>
                <p class="flex items-center gap-2 px-2 text-xs text-slate-500">
                    <ShieldCheck class="size-4" /> Card details are never
                    stored; only the last 4 digits are kept.
                </p>
                <Link
                    :href="showRequest(serviceRequest.reference)"
                    class="flex items-center gap-1 px-2 text-sm text-slate-600 hover:text-slate-900"
                >
                    <ArrowLeft class="size-4" /> Back to application
                </Link>
            </aside>
        </main>
    </div>
</template>
