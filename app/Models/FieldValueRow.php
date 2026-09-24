<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\FieldValueRowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * Class FieldValueRow
 *
 * One repetition of a repeater. It exists so that "phone number 2" knows its
 * label and its number belong together.
 *
 * Without a row, the values of a repeater are a flat bag and there is no way to
 * say which number goes with which label. It is a small model that removes an
 * entire category of ambiguity.
 *
 * @property int $id
 * @property int $account_id
 * @property string $valuable_type
 * @property int $valuable_id
 * @property int $field_id
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Contact|Entity|LifeEvent $valuable
 * @property-read Field $field
 */
#[Fillable([
    'account_id',
    'valuable_type',
    'valuable_id',
    'field_id',
    'position',
])]
class FieldValueRow extends Model
{
    /** @use HasFactory<FieldValueRowFactory> */
    use HasFactory;

    /**
     * The record a value belongs to, touched whenever the value is saved or
     * removed. A card's version is read from the record's `updated_at`, so a
     * value that moved without moving its record would hand a device a card
     * it already believes it has.
     *
     * @var array<int, string>
     */
    protected $touches = ['valuable'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * Get the account the row belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the record the repetition sits on, which is a contact or an entity.
     *
     * @return MorphTo<Model, $this>
     */
    public function valuable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the repeater this is a repetition of, which is always a field whose
     * `is_repeatable` is true.
     *
     * @return BelongsTo<Field, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * Get what was filled in on this repetition, in the order the repeater asks
     * its children for.
     *
     * @return HasMany<FieldValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(FieldValue::class);
    }
}
