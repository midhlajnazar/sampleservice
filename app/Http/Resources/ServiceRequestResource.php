<?php

namespace App\Http\Resources;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceRequest */
class ServiceRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status->toArray(),
            'channel' => $this->channel,
            // Nested resources are resolved to plain arrays so Inertia doesn't wrap them in "data".
            'service' => $this->whenLoaded('service', fn () => ServiceResource::make($this->service)->resolve($request)),
            'applicant' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'emirates_id' => $this->user->emirates_id,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee?->only('id', 'name')),
            'form_data' => $this->form_data,
            'answers' => $this->when($this->relationLoaded('service'), fn () => $this->readableAnswers()),
            'fees' => [
                'breakdown' => $this->fee_breakdown,
                'subtotal' => (float) $this->subtotal,
                'vat' => (float) $this->vat_amount,
                'total' => (float) $this->total_amount,
                'currency' => 'AED',
            ],
            'decision_note' => $this->decision_note,
            'certificate_number' => $this->certificate_number,
            'valid_until' => $this->valid_until?->toDateString(),
            'documents' => $this->whenLoaded('documents', fn () => DocumentResource::collection($this->documents)->resolve($request)),
            'payments' => $this->whenLoaded('payments', fn () => PaymentResource::collection($this->payments)->resolve($request)),
            'activities' => $this->whenLoaded('activities', fn () => ActivityResource::collection($this->activities)->resolve($request)),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
