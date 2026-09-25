<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\RepetitionOrderEnum;
use App\Exceptions\AcceptedFileKindIsNotValid;
use App\Exceptions\PublishedVersionIsFrozen;
use Carbon\CarbonImmutable;
use Database\Factories\FieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class Field
 *
 * The smallest unit of the personalisation engine: "Street", "City", "Shoe
 * size", "Diet". A field belongs to a section version, has a type, and carries
 * its own constraints.
 *
 * It is where three separate parts of the product meet. Accessibility lands
 * here because pages are generated from structures users invented: a type has
 * to render accessibly by construction, once, or every template anybody builds
 * becomes a new source of problems nobody can fix on their behalf. Any date can
 * drive a reminder rule, complete or coming round again, and the type is the
 * only thing that says so. And every field is searchable and filterable with no
 * configuration at all.
 *
 * The key is stable and unique inside the section. Renaming a label never
 * changes it, which is what lets an import, an export or an API caller keep
 * working after somebody has tidied up the wording.
 *
 * @property int $id
 * @property int $account_id
 * @property int $section_version_id
 * @property FieldTypeEnum $type
 * @property string $key
 * @property string $label
 * @property string|null $label_translation_key
 * @property string|null $help_text
 * @property string|null $placeholder
 * @property int $position
 * @property bool $is_required
 * @property bool $is_sensitive
 * @property bool $is_highlighted
 * @property bool $is_unique
 * @property bool $is_identifying
 * @property int|null $value_list_id
 * @property array<array-key, mixed>|null $default_value
 * @property array<array-key, mixed>|null $options
 * @property array<array-key, mixed>|null $validation
 * @property bool $is_repeatable
 * @property int|null $repeat_min
 * @property int|null $repeat_max
 * @property RepetitionOrderEnum|null $repetition_order
 * @property int|null $parent_field_id
 * @property RelationTargetEnum|null $relation_target
 * @property int|null $relation_entity_type_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read SectionVersion $sectionVersion
 * @property-read ValueList|null $valueList
 * @property-read Field|null $parent
 * @property-read EntityType|null $relationEntityType
 */
#[Fillable([
    'account_id',
    'section_version_id',
    'type',
    'key',
    'label',
    'label_translation_key',
    'help_text',
    'placeholder',
    'position',
    'is_required',
    'is_sensitive',
    'is_highlighted',
    'is_unique',
    'is_identifying',
    'value_list_id',
    'default_value',
    'options',
    'validation',
    'is_repeatable',
    'repeat_min',
    'repeat_max',
    'repetition_order',
    'parent_field_id',
    'relation_target',
    'relation_entity_type_id',
])]
class Field extends Model
{
    /** @use HasFactory<FieldFactory> */
    use HasFactory;

    /**
     * How long a key may be, which the column enforces and the action that
     * builds one has to respect while it is still making it unique.
     */
    public const int KEY_LENGTH = 64;

    /**
     * What a kind of file a file field takes has to look like: a mime type,
     * type and subtype, such as `image/png`. A structure file and the builder
     * are both held to it.
     */
    public const string MIME_TYPE_PATTERN = '#^[a-z0-9!\#$&^_.+-]+/[a-z0-9!\#$&^_.+-]+$#';

    /**
     * How many kinds of file one field may take.
     */
    public const int MAX_ACCEPTED_MIME_TYPES = 20;

    /**
     * Refuse the kinds of file a field of the given type is set to take when
     * one is not written as a mime type, when there are too many of them, or
     * when an image field is told to take something that is not an image.
     *
     * @param  array<string, mixed>|null  $options
     *
     * @throws AcceptedFileKindIsNotValid
     */
    public static function refuseKindsItCannotTake(FieldTypeEnum $type, ?array $options): void
    {
        $kinds = $options['accepted_mime_types'] ?? null;

        if ($kinds === null || ! $type->holdsAFile()) {
            return;
        }

        if (! is_array($kinds) || count($kinds) > self::MAX_ACCEPTED_MIME_TYPES) {
            throw new AcceptedFileKindIsNotValid('A field takes at most '.self::MAX_ACCEPTED_MIME_TYPES.' kinds of file.');
        }

        foreach ($kinds as $kind) {
            if (! is_string($kind) || preg_match(self::MIME_TYPE_PATTERN, $kind) !== 1) {
                throw new AcceptedFileKindIsNotValid('“'.(is_string($kind) ? $kind : '').'” is not a kind of file.');
            }

            if ($type !== FieldTypeEnum::File && ! str_starts_with($kind, 'image/')) {
                throw new AcceptedFileKindIsNotValid('An image field only takes kinds of image.');
            }
        }
    }

    /**
     * A field is as frozen as the section it sits in. It is created, edited and
     * deleted only on a draft, and the refusal lives here so that no path
     * around the actions can reach a published one.
     */
    #[Override]
    protected static function booted(): void
    {
        static::saving(function (Field $field): void {
            $field->refuseWhenTheSectionIsPublished();
        });

        static::deleting(function (Field $field): void {
            $field->refuseWhenTheSectionIsPublished();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'type' => FieldTypeEnum::class,
            'position' => 'integer',
            'is_required' => 'boolean',
            'is_sensitive' => 'boolean',
            'is_highlighted' => 'boolean',
            'is_unique' => 'boolean',
            'is_identifying' => 'boolean',
            'default_value' => 'array',
            'options' => 'array',
            'validation' => 'array',
            'is_repeatable' => 'boolean',
            'repeat_min' => 'integer',
            'repeat_max' => 'integer',
            'repetition_order' => RepetitionOrderEnum::class,
            'relation_target' => RelationTargetEnum::class,
        ];
    }

    /**
     * Get the account the field belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the section the field sits in.
     *
     * @return BelongsTo<SectionVersion, $this>
     */
    public function sectionVersion(): BelongsTo
    {
        return $this->belongsTo(SectionVersion::class);
    }

    /**
     * Get the list the choices come from, which only a choice field has.
     *
     * @return BelongsTo<ValueList, $this>
     */
    public function valueList(): BelongsTo
    {
        return $this->belongsTo(ValueList::class);
    }

    /**
     * Which kinds of file the field takes, as the mime types themselves.
     *
     * The list is written on the field rather than decided by the product,
     * because what counts as a document is a question about the template: one
     * account's "Contract" takes a PDF and nothing else, another's "Scan" takes
     * a photograph of one. An empty list is a field that has not said, and it
     * takes whatever the account has room for.
     *
     * @return list<string>
     */
    public function acceptedMimeTypes(): array
    {
        $accepted = $this->options['accepted_mime_types'] ?? [];

        return is_array($accepted) ? array_values(array_filter($accepted, 'is_string')) : [];
    }

    /**
     * Get the repeater this field sits inside, which is null when it sits
     * directly in the section.
     *
     * @return BelongsTo<Field, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'parent_field_id');
    }

    /**
     * Whether a screen can fill the field in. A field with no control of its own cannot, a relation
     * among them, and neither can a repeater asking on each repetition for such a field as
     * required, since no repetition of it could ever be saved.
     */
    public function canBeFilledIn(): bool
    {
        if (! $this->type->hasAnInput()) {
            return false;
        }

        if (! $this->type->isRepeater()) {
            return true;
        }

        return $this->children->doesntContain(
            fn (Field $child): bool => $child->is_required && ! $child->type->hasAnInput(),
        );
    }

    /**
     * Get what a repeater asks for on each repetition.
     *
     * @return HasMany<Field, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Field::class, 'parent_field_id')->orderBy('position');
    }

    /**
     * Get everything ever entered for this field, on every record.
     *
     * @return HasMany<FieldValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(FieldValue::class);
    }

    /**
     * Get every reminder rule reading this field, on every record.
     *
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get the kind of record a relation field points at, which is null when it
     * points at contacts.
     *
     * @return BelongsTo<EntityType, $this>
     */
    public function relationEntityType(): BelongsTo
    {
        return $this->belongsTo(EntityType::class, 'relation_entity_type_id');
    }

    /**
     * Get what the field is called. One the product supplied has no label of
     * its own until somebody gives it one, and reads in the language of whoever
     * is looking until then.
     *
     * @return Attribute<string, string>
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? __((string) $this->label_translation_key),
        );
    }

    private function refuseWhenTheSectionIsPublished(): void
    {
        $version = $this->sectionVersion;

        if ($version !== null && ! $version->status->isEditable()) {
            throw new PublishedVersionIsFrozen("Version {$version->version_number} of this section has been published. Make a draft from it instead.");
        }
    }
}
