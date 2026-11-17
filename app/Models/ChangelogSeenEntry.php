<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ChangelogSeenEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ChangelogSeenEntry
 *
 * Says that a person has seen one entry of What's new. The entry is a file in `changelog/`,
 * named here by its ULID, so a renamed or edited file keeps its readers.
 *
 * @property int $id
 * @property int $user_id
 * @property string $entry_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'entry_id',
])]
class ChangelogSeenEntry extends Model
{
    /** @use HasFactory<ChangelogSeenEntryFactory> */
    use HasFactory;

    /**
     * Get the person who saw the entry.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
