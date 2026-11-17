<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChangelogCommentStatusEnum;
use App\Models\Concerns\HasUuids;
use Carbon\CarbonImmutable;
use Database\Factories\ChangelogCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class ChangelogComment
 *
 * What a person wrote about one entry of What's new. A public comment is shown under the entry,
 * under the author's name or as Anonymous; a private one reaches the administrators only.
 * Administrators review it, answer it, and may hide a public one from What's new.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $entry_id
 * @property string $body
 * @property bool $is_private
 * @property bool $is_anonymous
 * @property ChangelogCommentStatusEnum $status
 * @property CarbonImmutable|null $reviewed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, ChangelogCommentReply> $replies
 */
#[Fillable([
    'user_id',
    'entry_id',
    'body',
    'is_private',
    'is_anonymous',
    'status',
    'reviewed_at',
])]
class ChangelogComment extends Model
{
    /** @use HasFactory<ChangelogCommentFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * Get the person who wrote it.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the answers administrators wrote, oldest first.
     *
     * @return HasMany<ChangelogCommentReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ChangelogCommentReply::class)->orderBy('id');
    }

    /**
     * Keep the comments anybody may read: public, and not hidden by an administrator.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function shownToEverybody(Builder $query): void
    {
        $query->where('is_private', false)
            ->where('status', '!=', ChangelogCommentStatusEnum::Hidden->value);
    }

    /**
     * Keep the comments one person may read: those shown to everybody, and their own.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user): void {
            $query->where('user_id', $user->id)
                ->orWhere(fn (Builder $query) => $query->shownToEverybody());
        });
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'is_anonymous' => 'boolean',
            'status' => ChangelogCommentStatusEnum::class,
            'reviewed_at' => 'datetime',
        ];
    }
}
