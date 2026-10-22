<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Service */
class ServiceResource extends JsonResource
{
    /** Include the form and document schemas (for apply/edit screens). */
    public bool $withSchema = false;

    public function withSchema(bool $value = true): static
    {
        $this->withSchema = $value;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'icon' => $this->icon,
            'summary' => $this->summary,
            'description' => $this->description,
            'base_fee' => (float) $this->base_fee,
            'vat_rate' => (float) $this->vat_rate,
            'currency' => 'AED',
            'processing_days' => $this->processing_days,
            'is_active' => $this->is_active,
            'eligibility' => $this->eligibility ?? [],
            'form_schema' => $this->when($this->withSchema, fn () => $this->form_schema),
            'document_schema' => $this->when($this->withSchema, fn () => $this->document_schema),
            'requests_count' => $this->whenCounted('requests'),
        ];
    }
}
