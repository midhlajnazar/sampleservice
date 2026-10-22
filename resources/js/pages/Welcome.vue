<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    Clock,
    CreditCard,
    FileUp,
    ListChecks,
} from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ServiceIcon from '@/components/portal/ServiceIcon.vue';
import { formatMoney } from '@/lib/portal';
import { dashboard, login, register } from '@/routes';
import { show as showService } from '@/routes/services';
import type { Service } from '@/types';

defineProps<{
    services: Service[];
}>();

const steps = [
    {
        icon: ListChecks,
        title: 'Choose a service',
        text: 'Pick the service and check the requirements and fees upfront.',
    },
    {
        icon: FileUp,
        title: 'Fill in & upload',
        text: 'Complete a guided form and upload your Emirates ID, passport and visa.',
    },
    {
        icon: CreditCard,
        title: 'Pay securely',
        text: 'Pay online by card. You get a receipt straight away.',
    },
    {
        icon: BadgeCheck,
        title: 'Track & receive',
        text: 'Follow every step of the review and download your certificate.',
    },
];
</script>

<template>
    <Head title="Government services, online" />

    <div class="min-h-screen bg-white text-neutral-900">
        <header class="border-b bg-white/80 backdrop-blur">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6"
            >
                <Link href="/" class="flex items-center gap-2 font-semibold">
                    <span
                        class="flex size-8 items-center justify-center rounded-md bg-primary text-white"
                    >
                        <AppLogoIcon class="size-5 fill-current" />
                    </span>
                    {{ $page.props.name }}
                </Link>
                <nav class="flex items-center gap-2 text-sm">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Go to dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-4 py-2 font-medium hover:bg-accent"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Create account
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <section class="bg-gradient-to-b from-teal-50 to-white">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
                <p
                    class="mb-3 inline-flex rounded-full bg-white px-3 py-1 text-xs font-medium text-primary ring-1 ring-teal-200"
                >
                    Available 24/7 · No queues
                </p>
                <h1
                    class="max-w-2xl text-4xl font-semibold tracking-tight sm:text-5xl"
                >
                    Renew licences and get certificates without visiting a
                    service centre.
                </h1>
                <p class="mt-4 max-w-xl text-lg text-neutral-600">
                    Apply online, upload your documents, pay by card and track
                    your application until it is approved.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a
                        href="#services"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Browse services <ArrowRight class="size-4" />
                    </a>
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="login()"
                        class="rounded-md border bg-white px-5 py-2.5 font-medium hover:bg-accent"
                    >
                        Track an application
                    </Link>
                </div>
            </div>
        </section>

        <section id="services" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2 class="text-2xl font-semibold tracking-tight">
                Popular services
            </h2>
            <p class="mt-1 text-neutral-600">
                Everything you need, with fees and processing times shown
                upfront.
            </p>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="service in services"
                    :key="service.id"
                    :href="showService(service.slug)"
                    class="group flex flex-col rounded-xl border p-5 transition hover:border-primary hover:shadow-md"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="flex size-10 items-center justify-center rounded-lg bg-teal-50 text-primary [&>svg]:size-5"
                        >
                            <ServiceIcon :icon="service.icon" />
                        </span>
                        <span class="text-xs text-neutral-500">{{
                            service.category
                        }}</span>
                    </div>
                    <h3 class="mt-4 font-semibold group-hover:text-primary">
                        {{ service.name }}
                    </h3>
                    <p class="mt-1 flex-1 text-sm text-neutral-600">
                        {{ service.summary }}
                    </p>
                    <div
                        class="mt-4 flex items-center justify-between border-t pt-3 text-sm"
                    >
                        <span class="font-medium"
                            >From {{ formatMoney(service.base_fee) }}</span
                        >
                        <span
                            class="inline-flex items-center gap-1 text-neutral-500"
                        >
                            <Clock class="size-3.5" />
                            {{ service.processing_days }} working day{{
                                service.processing_days > 1 ? 's' : ''
                            }}
                        </span>
                    </div>
                </Link>
            </div>
        </section>

        <section class="border-t bg-neutral-50">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <h2 class="text-2xl font-semibold tracking-tight">
                    How it works
                </h2>
                <ol class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <li v-for="(step, i) in steps" :key="step.title">
                        <span
                            class="flex size-10 items-center justify-center rounded-full bg-white text-primary ring-1 ring-teal-200"
                        >
                            <component :is="step.icon" class="size-5" />
                        </span>
                        <p class="mt-3 text-xs font-medium text-primary">
                            Step {{ i + 1 }}
                        </p>
                        <h3 class="font-semibold">{{ step.title }}</h3>
                        <p class="mt-1 text-sm text-neutral-600">
                            {{ step.text }}
                        </p>
                    </li>
                </ol>
            </div>
        </section>

        <footer class="border-t py-8 text-center text-sm text-neutral-500">
            {{ $page.props.name }}: a demo service portal built with Laravel,
            Inertia & Vue.
        </footer>
    </div>
</template>
