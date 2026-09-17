<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ValueListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class ValueList
 *
 * A named set of choices defined once per account and reused by any number of
 * fields: genders, phone labels, contact categories. They exist so that nobody
 * has to retype the same handful of options into every field that offers them.
 *
 * The account is created with a ready made set. Every one of them is the
 * user's: it can be renamed, reordered, added to and thrown away. Only the
 * `key` of a supplied list is stable, and it is stable so that the product can
 * find the list again, not so that the list stays as it was.
 *
 * A list is shared unless it says otherwise. One that is not holds the choices
 * somebody typed into a single field and belongs to that field: it is stored
 * here so there is one way to hold a set of choices rather than two, and it is
 * kept off the value lists screen and out of every picker, because it is not
 * part of the account's vocabulary and nobody named it.
 *
 * @property int $id
 * @property int $account_id
 * @property string|null $key
 * @property string $name
 * @property string|null $name_translation_key
 * @property string|null $description
 * @property bool $is_system
 * @property bool $allows_user_values
 * @property bool $is_shared
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 */
#[Fillable([
    'account_id',
    'key',
    'name',
    'name_translation_key',
    'description',
    'is_system',
    'allows_user_values',
    'is_shared',
    'position',
])]
class ValueList extends Model
{
    /** @use HasFactory<ValueListFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'allows_user_values' => 'boolean',
            'is_shared' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Get the account the list belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the choices the list offers.
     *
     * @return HasMany<ValueListItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ValueListItem::class);
    }

    /**
     * Get what the list is called. A list the product supplied has no name of
     * its own until somebody gives it one, and reads in the language of
     * whoever is looking until then. Writing to it stores the name as given,
     * which is how a supplied list stops following the interface language.
     *
     * @return Attribute<string, string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? __((string) $this->name_translation_key),
        );
    }
}
