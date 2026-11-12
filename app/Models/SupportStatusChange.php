<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupportRequestStatusEnum;
use Carbon\CarbonImmutable;
use Database\Factories\SupportStatusChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class SupportStatusChange
 *
 * One move of a support request from one status to another, with who moved it and when. Together
 * they are the status history the administrators read beside the thread.
 *
 * A row is written once and never changed. The name of whoever moved it is written down with it, so
 * the history still reads after that user has gone.
 *
 * @property int $id
 * @property int $support_request_id
 * @property SupportRequestStatusEnum|null $from_status
 * @property SupportRequestStatusEnum $to_status
 * @property int|null $user_id
 * @property string $user_name
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read SupportRequest $supportRequest
 * @property-read User|null $user
 */
#[Fillable([
    'support_request_id',
    'from_status',
    'to_status',
    'user_id',
    'user_name',
])]
class SupportStatusChange extends Model
{
    /** @use HasFactory<SupportStatusChangeFactory> */
    use HasFactory;

    /**
     * Get the request whose status changed.
     *
     * @return BelongsTo<SupportRequest, $this>
     */
    public function supportRequest(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class);
    }

    /**
     * Get the user who changed it, while that user exists.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'from_status' => SupportRequestStatusEnum::class,
            'to_status' => SupportRequestStatusEnum::class,
        ];
    }
}
