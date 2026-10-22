<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A service applicants can apply for. The application form and required
 * documents are described by JSON schemas so new services need no code.
 *
 * form_schema: list of sections, each { title, fields: [ {
 *     name, label, type (text|email|tel|number|date|textarea|select|radio|checkbox),
 *     required?, options?: [{ value, label, fee? }], placeholder?, help?,
 *     min?, max?, pattern?, show_if?: { field, value }
 * } ] }
 *
 * document_schema: list of { key, label, description?, required, mimes: [..], max_kb, show_if? }
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $category
 * @property string $icon
 * @property string $summary
 * @property string|null $description
 * @property string $base_fee
 * @property string $vat_rate
 * @property int $processing_days
 * @property array<int, array<string, mixed>> $form_schema
 * @property array<int, array<string, mixed>> $document_schema
 * @property array<int, string>|null $eligibility
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'slug', 'category', 'icon', 'summary', 'description', 'base_fee', 'vat_rate',
    'processing_days', 'form_schema', 'document_schema', 'eligibility', 'is_active', 'sort_order',
])]
class Service extends Model
{
    protected function casts(): array
    {
        return [
            'base_fee' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'form_schema' => 'array',
            'document_schema' => 'array',
            'eligibility' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<ServiceRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /** @param Builder<Service> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * All form fields flattened out of their sections, keyed by name.
     *
     * @return array<string, array<string, mixed>>
     */
    public function fields(): array
    {
        $fields = [];

        foreach ($this->form_schema as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $fields[$field['name']] = $field;
            }
        }

        return $fields;
    }
}
