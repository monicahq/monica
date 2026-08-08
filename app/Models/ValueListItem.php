<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ValueListItemFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property string $label
 * @property string|null $label_translation_key
 * @property string|null $description
 * @property string|null $colour
 * @property string|null $icon
 * @property int $position
 * @property bool $is_system
 * @property bool $is_active
 * @property int $usage_count
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Account $account
 * @property-read ValueList $valueList
 */
class ValueListItem extends Model
{
    /** @use HasFactory<ValueListItemFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'value_list_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'value_list_id',
        'key',
        'label',
        'label_translation_key',
        'description',
        'colour',
        'icon',
        'position',
        'is_system',
        'is_active',
        'usage_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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
     * Get what the item is called. An item the product supplied has no label
     * of its own until somebody gives it one, and reads in the language of
     * whoever is looking until then. Writing to it stores the label as given,
     * which is how a supplied item stops following the interface language.
     *
     * @return Attribute<string, string>
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? __((string) $this->label_translation_key),
        );
    }
}
