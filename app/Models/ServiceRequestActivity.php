<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $service_request_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $from_status
 * @property string|null $to_status
 * @property string|null $comment
 * @property bool $is_internal
 * @property Carbon|null $created_at
 * @property-read User|null $user
 */
#[Fillable(['service_request_id', 'user_id', 'action', 'from_status', 'to_status', 'comment', 'is_internal'])]
class ServiceRequestActivity extends Model
{
    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
