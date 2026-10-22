<?php

namespace App\Http\Resources;

use App\Enums\RequestStatus;
use App\Models\ServiceRequestActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceRequestActivity */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'from_status' => $this->from_status ? RequestStatus::from($this->from_status)->toArray() : null,
            'to_status' => $this->to_status ? RequestStatus::from($this->to_status)->toArray() : null,
            'comment' => $this->comment,
            'is_internal' => $this->is_internal,
            'actor' => $this->user->name ?? 'System',
            'actor_is_admin' => (bool) $this->user?->isAdmin(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
