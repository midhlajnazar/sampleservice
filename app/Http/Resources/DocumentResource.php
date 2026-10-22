<?php

namespace App\Http\Resources;

use App\Models\ServiceRequestDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceRequestDocument */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->label,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'status' => $this->status->value,
            'review_note' => $this->review_note,
            'url' => $request->is('api/*') ? route('api.v1.documents.show', $this->id) : route('documents.show', $this->id),
            'uploaded_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
