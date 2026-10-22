<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $service_request_id
 * @property string $key
 * @property string $label
 * @property string $original_name
 * @property string $path
 * @property string $mime_type
 * @property int $size
 * @property DocumentStatus $status
 * @property string|null $review_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ServiceRequest $serviceRequest
 */
#[Fillable(['service_request_id', 'key', 'label', 'original_name', 'path', 'mime_type', 'size', 'status', 'review_note'])]
class ServiceRequestDocument extends Model
{
    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
        ];
    }

    /** @return BelongsTo<ServiceRequest, $this> */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
