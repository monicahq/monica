<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChangelogVoteDirectionEnum;
use Carbon\CarbonImmutable;
use Database\Factories\ChangelogVoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class ChangelogVote
 *
 * Whether a person likes one entry of What's new or not. A person holds at most one vote per
 * entry, and the entry is named by its ULID.
 *
 * @property int $id
 * @property int $user_id
 * @property string $entry_id
 * @property ChangelogVoteDirectionEnum $direction
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'entry_id',
    'direction',
])]
class ChangelogVote extends Model
{
    /** @use HasFactory<ChangelogVoteFactory> */
    use HasFactory;

    /**
     * Get the person who voted.
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
            'direction' => ChangelogVoteDirectionEnum::class,
        ];
    }
}
