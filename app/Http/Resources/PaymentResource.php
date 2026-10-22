<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_reference' => $this->transaction_reference,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'gateway' => $this->gateway,
            'card_brand' => $this->card_brand,
            'card_last4' => $this->card_last4,
            'failure_reason' => $this->failure_reason,
            'authorization_code' => $this->gateway_response['authorization_code'] ?? null,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'service_request' => $this->whenLoaded('serviceRequest', fn () => [
                'reference' => $this->serviceRequest->reference,
                'service' => $this->serviceRequest->service->name,
                'applicant' => $this->serviceRequest->user->name,
            ]),
        ];
    }
}
