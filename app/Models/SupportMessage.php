<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupportMessageKindEnum;
use App\Models\Concerns\HasUuids;
use Carbon\CarbonImmutable;
use Database\Factories\SupportMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class SupportMessage
 *
 * One entry in the thread of a support request: something the user wrote, a reply from an
 * administrator, or an internal note administrators leave for each other.
 *
 * The author's name is written down with the message, so a reply stays signed after the
 * administrator who wrote it has given up the role or left the instance.
 *
 * @property int $id
 * @property string $uuid
 * @property int $support_request_id
 * @property int|null $author_user_id
 * @property string $author_name
 * @property SupportMessageKindEnum $kind
 * @property string $body
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read SupportRequest $supportRequest
 * @property-read User|null $author
 * @property-read Collection<int, SupportAttachment> $attachments
 */
#[Fillable([
    'support_request_id',
    'author_user_id',
    'author_name',
    'kind',
    'body',
])]
class SupportMessage extends Model
{
    /** @use HasFactory<SupportMessageFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * Get the request the message belongs to.
     *
     * @return BelongsTo<SupportRequest, $this>
     */
    public function supportRequest(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class);
    }

    /**
     * Get the user who wrote it, while that user exists.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    /**
     * Get the files sent with it.
     *
     * @return HasMany<SupportAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportAttachment::class)->orderBy('id');
    }

    /**
     * Whether an administrator wrote it rather than the person who asked.
     */
    public function isFromSupportTeam(): bool
    {
        return $this->kind !== SupportMessageKindEnum::Message;
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'kind' => SupportMessageKindEnum::class,
        ];
    }
}
