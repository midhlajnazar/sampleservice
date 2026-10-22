<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property int $user_id
 * @property int $service_id
 * @property RequestStatus $status
 * @property array<string, mixed> $form_data
 * @property list<array{label: string, amount: float}> $fee_breakdown
 * @property string $subtotal
 * @property string $vat_amount
 * @property string $total_amount
 * @property string $channel
 * @property int|null $assigned_to
 * @property string|null $decision_note
 * @property string|null $certificate_number
 * @property Carbon|null $valid_until
 * @property Carbon|null $submitted_at
 * @property Carbon|null $decided_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Service $service
 * @property-read User|null $assignee
 * @property-read Payment|null $latestPayment
 */
#[Fillable([
    'reference', 'user_id', 'service_id', 'status', 'form_data', 'fee_breakdown', 'subtotal',
    'vat_amount', 'total_amount', 'channel', 'assigned_to', 'decision_note', 'certificate_number',
    'valid_until', 'submitted_at', 'decided_at',
])]
class ServiceRequest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'form_data' => 'array',
            'fee_breakdown' => 'array',
            'subtotal' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'valid_until' => 'date',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<ServiceRequestDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ServiceRequestDocument::class);
    }

    /** @return HasMany<ServiceRequestActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(ServiceRequestActivity::class)->latest('id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /** @return HasOne<Payment, $this> */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function isPaid(): bool
    {
        return $this->payments()->where('status', PaymentStatus::Paid)->exists();
    }

    public function awaitingPayment(): bool
    {
        return $this->status === RequestStatus::PendingPayment;
    }

    /**
     * Form answers paired with their labels (and option labels), for display.
     *
     * @return list<array{section: string, label: string, value: string}>
     */
    public function readableAnswers(): array
    {
        $answers = [];

        foreach ($this->service->form_schema as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $value = $this->form_data[$field['name']] ?? null;

                if ($value === null || $value === '' || $value === []) {
                    continue;
                }

                $options = array_column($field['options'] ?? [], 'label', 'value');
                $display = collect((array) $value)
                    ->map(fn ($v) => is_bool($v) ? ($v ? 'Yes' : 'No') : ($options[$v] ?? (string) $v))
                    ->implode(', ');

                $answers[] = ['section' => $section['title'], 'label' => $field['label'], 'value' => $display];
            }
        }

        return $answers;
    }
}
