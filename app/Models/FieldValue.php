<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\FieldValueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class FieldValue
 *
 * What somebody actually typed. One row per field per record, or per field per
 * repeater row. It is the largest table in the product by a wide margin, and
 * the one every screen reads from.
 *
 * Two decisions shape it. The first is typed columns instead of a single json
 * blob: a blob is smaller to write and cannot be filtered on, and structured
 * filtering on any custom field with no configuration is what the product
 * promises. The second is the detached state: when a field is removed from a
 * structure its values are not destroyed, they are set aside, explained, and
 * restorable.
 *
 * @property int $id
 * @property int $account_id
 * @property int $vault_id
 * @property string $valuable_type
 * @property int $valuable_id
 * @property int $field_id
 * @property string $field_key
 * @property int|null $field_value_row_id
 * @property string|null $value_string
 * @property string|null $value_text
 * @property string|null $value_number
 * @property bool|null $value_boolean
 * @property Carbon|null $value_date
 * @property Carbon|null $value_datetime
 * @property array<array-key, mixed>|null $value_json
 * @property int|null $value_list_item_id
 * @property array<array-key, mixed>|null $value_list_item_ids
 * @property int|null $value_file_id
 * @property int|null $related_contact_id
 * @property int|null $related_entity_id
 * @property bool $year_is_unknown
 * @property bool $month_is_unknown
 * @property bool $is_detached
 * @property Carbon|null $detached_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read Contact|Entity $valuable
 * @property-read Field $field
 * @property-read FieldValueRow|null $row
 * @property-read ValueListItem|null $valueListItem
 * @property-read File|null $file
 * @property-read Contact|null $relatedContact
 * @property-read Entity|null $relatedEntity
 */
class FieldValue extends Model
{
    /** @use HasFactory<FieldValueFactory> */
    use HasFactory;

    /**
     * The year a date that comes round again is stored under when nobody knows
     * the real one. A birthday whose year is unknown is a normal case rather
     * than an error, and the column still has to hold a date, so it holds this
     * one. It is never rendered and never used to compute an age.
     */
    public const int PLACEHOLDER_YEAR = 1900;

    protected $table = 'field_values';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'vault_id',
        'valuable_type',
        'valuable_id',
        'field_id',
        'field_key',
        'field_value_row_id',
        'value_string',
        'value_text',
        'value_number',
        'value_boolean',
        'value_date',
        'value_datetime',
        'value_json',
        'value_list_item_id',
        'value_list_item_ids',
        'value_file_id',
        'related_contact_id',
        'related_entity_id',
        'year_is_unknown',
        'month_is_unknown',
        'is_detached',
        'detached_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_boolean' => 'boolean',
            'value_date' => 'date',
            'value_datetime' => 'datetime',
            'value_json' => 'array',
            'value_list_item_ids' => 'array',
            'year_is_unknown' => 'boolean',
            'month_is_unknown' => 'boolean',
            'is_detached' => 'boolean',
            'detached_at' => 'datetime',
        ];
    }

    /**
     * Get the account the value belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault the value belongs to. It is carried on the row rather than
     * reached through the record, so filtering never needs a join.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the record the value was written on, which is a contact or an entity.
     *
     * @return MorphTo<Model, $this>
     */
    public function valuable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the field the value was entered for.
     *
     * @return BelongsTo<Field, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * Get the repetition the value sits in, which is null when the field is not
     * inside a repeater.
     *
     * @return BelongsTo<FieldValueRow, $this>
     */
    public function row(): BelongsTo
    {
        return $this->belongsTo(FieldValueRow::class, 'field_value_row_id');
    }

    /**
     * Get the chosen item, which only a single choice field has.
     *
     * @return BelongsTo<ValueListItem, $this>
     */
    public function valueListItem(): BelongsTo
    {
        return $this->belongsTo(ValueListItem::class, 'value_list_item_id');
    }

    /**
     * Get the attached file, which only a file, image or avatar field has.
     *
     * @return BelongsTo<File, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'value_file_id');
    }

    /**
     * Get the contact a relation field points at.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function relatedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'related_contact_id');
    }

    /**
     * Get the entity a relation field points at.
     *
     * @return BelongsTo<Entity, $this>
     */
    public function relatedEntity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'related_entity_id');
    }

    /**
     * Scope the query to the values a page renders. A detached one is kept and
     * explained on a screen of its own rather than shown among the rest.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeAttached(Builder $query): void
    {
        $query->where('is_detached', false);
    }

    /**
     * Get whatever was written, read out of the one column the type of the
     * field points at. A relation reads out of whichever of the two targets it
     * points at, since its type alone does not say.
     *
     * @return Attribute<mixed, never>
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: function (): mixed {
                if ($this->field->type->isRelation()) {
                    return $this->related_contact_id ?? $this->related_entity_id;
                }

                $column = $this->field->type->valueColumn();

                return $column === null ? null : $this->$column;
            },
        );
    }
}
