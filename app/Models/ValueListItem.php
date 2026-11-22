<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\CountryCodes;
use Carbon\CarbonImmutable;
use Database\Factories\ValueListItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class ValueListItem
 *
 * One choice inside a value list. "Mobile" inside phone labels, "Close friend"
 * inside contact categories.
 *
 * An item is disabled rather than deleted once anything points at it: every
 * record that already uses it stays untouched and readable, and the item
 * simply stops being offered. `usage_count` is what makes that cost visible,
 * and it is kept in step on write rather than counted on read.
 *
 * @property int $id
 * @property int $account_id
 * @property int $value_list_id
 * @property string|null $key
 * @property string|null $country_code
 * @property string $label
 * @property string|null $label_translation_key
 * @property string|null $description
 * @property string|null $colour
 * @property string|null $icon
 * @property int $position
 * @property bool $is_system
 * @property bool $is_active
 * @property int $usage_count
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read ValueList $valueList
 */
#[Fillable([
    'account_id',
    'value_list_id',
    'key',
    'country_code',
    'label',
    'label_translation_key',
    'description',
    'colour',
    'icon',
    'position',
    'is_system',
    'is_active',
    'usage_count',
])]
class ValueListItem extends Model
{
    public const string OPEN_KEY = 'custom...';

    /** @use HasFactory<ValueListItemFactory> */
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
            'position' => 'integer',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'usage_count' => 'integer',
        ];
    }

    /**
     * Get the account the item belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the list the item belongs to.
     *
     * @return BelongsTo<ValueList, $this>
     */
    public function valueList(): BelongsTo
    {
        return $this->belongsTo(ValueList::class);
    }

    /**
     * Whether picking this item asks for a wording of its own. The open item
     * is the one somebody reaches for when none of the others fit, so it names
     * no wording itself and takes whatever is typed beside it.
     */
    public function takesATypedWording(): bool
    {
        return $this->key === self::OPEN_KEY;
    }

    /**
     * Get the country this item stands for, as an upper case ISO 3166 code.
     * A supplied country keeps its code in `key`, one picked or given a code
     * in the settings keeps it in `country_code`. Null when the item names no
     * country of the standard.
     */
    public function countryCode(): ?string
    {
        $code = mb_strtoupper((string) ($this->country_code ?? $this->key));

        return in_array($code, CountryCodes::known(), true) ? $code : null;
    }

    /**
     * Whether the item reads as its country, in the reader's language. A
     * country whose label the account wrote reads as written instead, so it
     * does not stand for the plain country of the full list.
     */
    public function readsAsItsCountry(): bool
    {
        return $this->getRawOriginal('label') === null && $this->countryCode() !== null;
    }

    /**
     * Get what the item is called. An item the product supplied has no label
     * of its own until somebody gives it one, and reads in the language of
     * whoever is looking until then. Writing to it stores the label as given,
     * which is how a supplied item stops following the interface language.
     * A country picked in the settings has neither label nor translation key,
     * and reads as the country's name in the reader's language.
     *
     * @return Attribute<string, string>
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? $this->unwrittenLabel(),
        );
    }

    /**
     * Get the label of an item that has none of its own: its translation,
     * or the name of its country.
     */
    private function unwrittenLabel(): string
    {
        if ($this->label_translation_key !== null) {
            return __($this->label_translation_key);
        }

        $code = $this->countryCode();

        return $code === null ? '' : (string) CountryCodes::nameOf($code, app()->getLocale());
    }
}
