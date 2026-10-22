<?php

namespace App\Services;

use App\Models\Service;

/**
 * Computes the amount due for an application: the service's base fee plus any
 * fees attached to the options the applicant picked, plus VAT.
 */
class FeeCalculator
{
    /**
     * @param  array<string, mixed>  $answers
     * @return array{breakdown: list<array{label: string, amount: float}>, subtotal: float, vat: float, total: float}
     */
    public function calculate(Service $service, array $answers): array
    {
        $breakdown = [['label' => $service->name.' fee', 'amount' => (float) $service->base_fee]];

        foreach ($service->fields() as $name => $field) {
            foreach ($field['options'] ?? [] as $option) {
                if (empty($option['fee']) || ! in_array($option['value'], (array) ($answers[$name] ?? []), true)) {
                    continue;
                }

                $breakdown[] = ['label' => "{$field['label']}: {$option['label']}", 'amount' => (float) $option['fee']];
            }
        }

        $subtotal = round(array_sum(array_column($breakdown, 'amount')), 2);
        $vat = round($subtotal * (float) $service->vat_rate / 100, 2);

        return ['breakdown' => $breakdown, 'subtotal' => $subtotal, 'vat' => $vat, 'total' => round($subtotal + $vat, 2)];
    }
}
