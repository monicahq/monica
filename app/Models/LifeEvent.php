<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DatePrecisionEnum;
use App\Enums\LifeEventSourceEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\LifeEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class LifeEvent
 *
 * Something that happened, on a date, possibly with people. It is the unit both halves of the
 * product share: noting a lunch with Pam on Tuesday fills the Tuesday journal and Pam's history at
 * once.
 *
 * Despite the name it is not only a milestone. A call, a meal and a visit are life events exactly as
 * a marriage, a move and a new job are, and nothing about the type makes one count more than another.
 *
 * The concept is fixed; its type is the configurable part. The type gives the custom fields, through
 * the structure it owns, and a life event names the version of that structure it was recorded
 * against, so its values keep the shape they were entered in when the type changes later.
 *
 * Participation is the only thing that puts a life event on a contact's timeline. Naming somebody in
 * the narrative does not.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $life_event_type_id
 * @property int $structure_version_id
 * @property CarbonImmutable $happened_on
 * @property CarbonImmutable|null $ended_on
 * @property DatePrecisionEnum $date_precision
 * @property string|null $happened_at_time
 * @property int|null $duration_in_minutes
 * @property string|null $timezone
 * @property string|null $title
 * @property string|null $narrative
 * @property string|null $location
 * @property string|null $latitude
 * @property string|null $longitude
 * @property bool $is_shareable
 * @property bool $narrative_is_shareable
 * @property LifeEventSourceEnum $created_from
 * @property int|null $created_by_user_id
 * @property int $participant_count
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $name
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read LifeEventType $lifeEventType
 * @property-read StructureVersion $structureVersion
 * @property-read User|null $createdBy
 * @property-read Collection<int, LifeEventParticipant> $participants
 * @property-read Collection<int, FieldValue> $fieldValues
 * @property-read Collection<int, FieldValueRow> $fieldValueRows
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'life_event_type_id',
    'structure_version_id',
    'happened_on',
    'ended_on',
    'date_precision',
    'happened_at_time',
    'duration_in_minutes',
    'timezone',
    'title',
    'narrative',
    'location',
    'latitude',
    'longitude',
    'is_shareable',
    'narrative_is_shareable',
    'created_from',
    'created_by_user_id',
    'participant_count',
])]
class LifeEvent extends Model
{
    /** @use HasFactory<LifeEventFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;
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
            'happened_on' => 'date',
            'ended_on' => 'date',
            'date_precision' => DatePrecisionEnum::class,
            'created_from' => LifeEventSourceEnum::class,
            'is_shareable' => 'boolean',
            'narrative_is_shareable' => 'boolean',
        ];
    }

    /**
     * Get the account the life event belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault it lives in.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get its nature. A type deleted since still names the life events recorded as it.
     *
     * @return BelongsTo<LifeEventType, $this>
     */
    public function lifeEventType(): BelongsTo
    {
        return $this->belongsTo(LifeEventType::class)->withTrashed();
    }

    /**
     * Get the version of its type's structure it was recorded against.
     *
     * @return BelongsTo<StructureVersion, $this>
     */
    public function structureVersion(): BelongsTo
    {
        return $this->belongsTo(StructureVersion::class);
    }

    /**
     * Get the user who wrote it down, which never means that Me took part.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get who was there, in the order they are listed.
     *
     * @return HasMany<LifeEventParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(LifeEventParticipant::class)->orderBy('position');
    }

    /**
     * Get the values of the custom fields its type asks for.
     *
     * @return MorphMany<FieldValue, $this>
     */
    public function fieldValues(): MorphMany
    {
        return $this->morphMany(FieldValue::class, 'valuable');
    }

    /**
     * Get the repetitions of every repeater among those fields.
     *
     * @return MorphMany<FieldValueRow, $this>
     */
    public function fieldValueRows(): MorphMany
    {
        return $this->morphMany(FieldValueRow::class, 'valuable');
    }

    /**
     * Get what the life event is called: its title, or the name of its type when it has none.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->title ?? $this->lifeEventType->name,
        );
    }
}
