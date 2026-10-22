<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $transaction_reference
 * @property int $service_request_id
 * @property int $user_id
 * @property string $amount
 * @property string $currency
 * @property PaymentStatus $status
 * @property string $gateway
 * @property string|null $method
 * @property string|null $card_brand
 * @property string|null $card_last4
 * @property string|null $failure_reason
 * @property array<string, mixed>|null $gateway_response
 * @property Carbon|null $paid_at
 * @property Carbon|null $refunded_at
 * @property Carbon|null $created_at
 * @property-read ServiceRequest $serviceRequest
 * @property-read User $user
 */
#[Fillable([
    'transaction_reference', 'service_request_id', 'user_id', 'amount', 'currency', 'status', 'gateway',
    'method', 'card_brand', 'card_last4', 'failure_reason', 'gateway_response', 'paid_at', 'refunded_at',
])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'transaction_reference';
    }

    /** @return BelongsTo<ServiceRequest, $this> */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
