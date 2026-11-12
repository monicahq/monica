<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PossessionFieldValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PossessionFieldValue
 *
 * What one possession holds for one field of its type: the plate of a car,
 * the date a warranty ends. There is one per possession and field.
 *
 * The value is kept as text, normalised by the kind of the field: text and
 * choices as written, a number as a decimal string, a date as Y-m-d, yes or
 * no as 1 or 0, a rating as 1 to 5. A choice keeps its wording when the option
 * is later renamed or removed; only a write checks the current options.
 *
 * Deleting the field, or changing its kind, deletes its values.
 *
 * @property int $id
 * @property int $account_id
 * @property int $possession_id
 * @property int $possession_field_id
 * @property string $value
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Possession $possession
 * @property-read PossessionField $field
 */
#[Fillable([
    'account_id',
    'possession_id',
    'possession_field_id',
    'value',
])]
class PossessionFieldValue extends Model
{
    /** @use HasFactory<PossessionFieldValueFactory> */
    use HasFactory;

    /**
     * Get the possession holding the value.
     *
     * @return BelongsTo<Possession, $this>
     */
    public function possession(): BelongsTo
    {
        return $this->belongsTo(Possession::class);
    }

    /**
     * Get the field the value answers.
     *
     * @return BelongsTo<PossessionField, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(PossessionField::class, 'possession_field_id');
    }
}
