<?php

namespace App\Services\Payments;

use Illuminate\Support\Str;

/**
 * A fake card gateway ("SamplePay") that behaves like a real one for testing.
 * Outcomes are decided by the card number, Stripe-style:
 *
 *   4242 4242 4242 4242  approved (Visa)
 *   5555 5555 5555 4444  approved (Mastercard)
 *   4000 0000 0000 0002  declined
 *   4000 0000 0000 9995  insufficient funds
 *   4000 0000 0000 0069  expired card
 *
 * Any other number that passes the Luhn check is approved.
 */
class DummyPaymentGateway
{
    /**
     * @return list<array{number: string, brand: string, outcome: string}>
     */
    public static function testCards(): array
    {
        return [
            ['number' => '4242 4242 4242 4242', 'brand' => 'Visa', 'outcome' => 'Approved'],
            ['number' => '5555 5555 5555 4444', 'brand' => 'Mastercard', 'outcome' => 'Approved'],
            ['number' => '4000 0000 0000 0002', 'brand' => 'Visa', 'outcome' => 'Declined'],
            ['number' => '4000 0000 0000 9995', 'brand' => 'Visa', 'outcome' => 'Insufficient funds'],
            ['number' => '4000 0000 0000 0069', 'brand' => 'Visa', 'outcome' => 'Expired card'],
        ];
    }

    /**
     * Charge a card. Never throws for a business failure; check `approved`.
     *
     * @return array{approved: bool, authorization_code: string|null, brand: string, last4: string, message: string, raw: array<string, mixed>}
     */
    public function charge(string $cardNumber, float $amount, string $currency, string $reference): array
    {
        $number = preg_replace('/\D/', '', $cardNumber) ?? '';
        $brand = $this->brand($number);
        $failure = $this->failureFor($number);
        $authCode = $failure ? null : strtoupper(Str::random(6));

        return [
            'approved' => $failure === null,
            'authorization_code' => $authCode,
            'brand' => $brand,
            'last4' => substr($number, -4),
            'message' => $failure ?? 'Approved',
            'raw' => [
                'gateway' => 'samplepay',
                'gateway_txn_id' => 'SP-'.strtoupper(Str::random(12)),
                'merchant_reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'response_code' => $failure ? '05' : '00',
                'authorization_code' => $authCode,
                'processed_at' => now()->toIso8601String(),
            ],
        ];
    }

    /** Why a card number is declined, or null when it is approved. */
    public function failureFor(string $number): ?string
    {
        return match ($number) {
            '4000000000000002' => 'Card declined by issuer.',
            '4000000000009995' => 'Insufficient funds.',
            '4000000000000069' => 'Card has expired.',
            default => $this->passesLuhn($number) ? null : 'Invalid card number.',
        };
    }

    public function brand(string $number): string
    {
        return match (true) {
            str_starts_with($number, '4') => 'Visa',
            (bool) preg_match('/^5[1-5]/', $number) => 'Mastercard',
            (bool) preg_match('/^3[47]/', $number) => 'Amex',
            default => 'Card',
        };
    }

    public function passesLuhn(string $number): bool
    {
        if (! preg_match('/^\d{13,19}$/', $number)) {
            return false;
        }

        $sum = 0;
        foreach (array_reverse(str_split($number)) as $i => $digit) {
            $digit = (int) $digit;
            if ($i % 2 === 1) {
                $digit *= 2;
                $digit = $digit > 9 ? $digit - 9 : $digit;
            }
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
