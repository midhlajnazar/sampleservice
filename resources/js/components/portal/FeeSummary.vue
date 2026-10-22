<script setup lang="ts">
import { formatMoney } from '@/lib/portal';
import type { FeeBreakdown } from '@/types';

defineProps<{
    fees: FeeBreakdown;
    vatRate?: number;
}>();
</script>

<template>
    <dl class="space-y-2 text-sm">
        <div
            v-for="(line, i) in fees.breakdown"
            :key="i"
            class="flex justify-between gap-4"
        >
            <dt class="text-muted-foreground">{{ line.label }}</dt>
            <dd class="tabular-nums">{{ formatMoney(line.amount) }}</dd>
        </div>
        <div class="flex justify-between gap-4 border-t pt-2">
            <dt class="text-muted-foreground">
                VAT<span v-if="vatRate !== undefined"> ({{ vatRate }}%)</span>
            </dt>
            <dd class="tabular-nums">{{ formatMoney(fees.vat) }}</dd>
        </div>
        <div
            class="flex justify-between gap-4 border-t pt-2 text-base font-semibold"
        >
            <dt>Total</dt>
            <dd class="tabular-nums">{{ formatMoney(fees.total) }}</dd>
        </div>
    </dl>
</template>
