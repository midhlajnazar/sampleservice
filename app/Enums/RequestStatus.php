<?php

namespace App\Enums;

enum RequestStatus: string
{
    case PendingPayment = 'pending_payment';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case InfoRequested = 'info_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending payment',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::InfoRequested => 'Information requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Statuses an admin may move a request to from the current status.
     *
     * @return list<self>
     */
    public function adminTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::UnderReview, self::InfoRequested, self::Rejected],
            self::UnderReview => [self::InfoRequested, self::Approved, self::Rejected],
            self::InfoRequested => [self::UnderReview, self::Rejected],
            self::Approved => [self::Completed],
            default => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->adminTransitions(), true);
    }

    /** Whether the request is still open (not finalised). */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Rejected, self::Completed, self::Cancelled], true);
    }

    /**
     * @return array{value: string, label: string}
     */
    public function toArray(): array
    {
        return ['value' => $this->value, 'label' => $this->label()];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => $status->toArray(), self::cases());
    }
}
