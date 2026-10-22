<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Check, Copy, KeyRound, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, index, store } from '@/routes/api-tokens';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'API tokens', href: index() }],
    },
});

const props = defineProps<{
    tokens: {
        id: number;
        name: string;
        abilities: string[];
        last_used_at: string | null;
        expires_at: string | null;
        created_at: string;
    }[];
    availableAbilities: Record<string, string>;
    apiBaseUrl: string;
}>();

const page = usePage();
const newToken = computed(() => page.flash.newToken);
const copied = ref(false);

const form = useForm({
    name: '',
    abilities: Object.keys(props.availableAbilities).filter(
        (a) => a !== 'admin',
    ),
    expires_in_days: '' as string | number,
});

function createToken() {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset('name'),
    });
}

function revoke(id: number) {
    if (confirm('Revoke this token? Apps using it will stop working.')) {
        router.delete(destroy.url(id), { preserveScroll: true });
    }
}

async function copy(text: string) {
    await navigator.clipboard.writeText(text);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

const curlExample = computed(
    () =>
        `curl -H "Authorization: Bearer ${newToken.value ?? '<your-token>'}" \\\n     -H "Accept: application/json" \\\n     ${props.apiBaseUrl}/requests`,
);
</script>

<template>
    <Head title="API tokens" />

    <h1 class="sr-only">API tokens</h1>

    <div class="space-y-8">
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Create API token"
                description="Tokens let mobile apps, scripts or partner systems use the SampleService API on your behalf."
            />

            <div
                v-if="newToken"
                class="space-y-2 rounded-lg border border-emerald-200 bg-emerald-50 p-4"
            >
                <p class="text-sm font-medium text-emerald-900">
                    Copy your new token now. For security it will not be shown
                    again.
                </p>
                <div class="flex gap-2">
                    <code
                        class="flex-1 overflow-x-auto rounded-md bg-white px-3 py-2 font-mono text-sm ring-1 ring-emerald-200"
                        >{{ newToken }}</code
                    >
                    <Button
                        variant="outline"
                        size="icon"
                        :aria-label="copied ? 'Copied' : 'Copy token'"
                        @click="copy(newToken)"
                    >
                        <Check v-if="copied" class="size-4" />
                        <Copy v-else class="size-4" />
                    </Button>
                </div>
            </div>

            <form class="space-y-5" @submit.prevent="createToken">
                <div class="grid gap-2">
                    <Label for="token-name">Token name</Label>
                    <Input
                        id="token-name"
                        v-model="form.name"
                        placeholder="e.g. Mobile app, CRM integration"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">
                        Permissions
                    </legend>
                    <label
                        v-for="(description, ability) in availableAbilities"
                        :key="ability"
                        class="flex items-start gap-3 rounded-lg border p-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent"
                    >
                        <input
                            v-model="form.abilities"
                            type="checkbox"
                            class="mt-0.5 size-4 accent-primary"
                            :value="ability"
                        />
                        <span>
                            <span class="font-mono text-xs font-medium">{{
                                ability
                            }}</span>
                            <span class="block text-muted-foreground">{{
                                description
                            }}</span>
                        </span>
                    </label>
                    <InputError :message="form.errors.abilities" />
                </fieldset>

                <div class="grid gap-2">
                    <Label for="expires">Expires</Label>
                    <select
                        id="expires"
                        v-model="form.expires_in_days"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="">Never</option>
                        <option :value="7">In 7 days</option>
                        <option :value="30">In 30 days</option>
                        <option :value="90">In 90 days</option>
                        <option :value="365">In 1 year</option>
                    </select>
                </div>

                <Button :disabled="form.processing">Create token</Button>
            </form>
        </div>

        <div class="space-y-4">
            <Heading
                variant="small"
                title="Active tokens"
                description="Revoke any token you no longer use."
            />
            <ul v-if="tokens.length" class="divide-y rounded-lg border">
                <li
                    v-for="token in tokens"
                    :key="token.id"
                    class="flex items-start gap-3 p-4"
                >
                    <KeyRound class="mt-0.5 size-4 text-muted-foreground" />
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="font-medium">{{ token.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            Created {{ token.created_at }} · Last used
                            {{ token.last_used_at ?? 'never' }}
                            <template v-if="token.expires_at">
                                · Expires {{ token.expires_at }}</template
                            >
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <span
                                v-for="ability in token.abilities"
                                :key="ability"
                                class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs"
                                >{{ ability }}</span
                            >
                        </div>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="`Revoke ${token.name}`"
                        @click="revoke(token.id)"
                    >
                        <Trash2 class="size-4 text-destructive" />
                    </Button>
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">No tokens yet.</p>
        </div>

        <div class="space-y-3">
            <Heading
                variant="small"
                title="Using the API"
                description="Send the token as a Bearer token. Full endpoint list is in the project README."
            />
            <pre
                class="overflow-x-auto rounded-lg bg-neutral-900 p-4 font-mono text-xs leading-relaxed text-neutral-100"
                >{{ curlExample }}</pre>
        </div>
    </div>
</template>
