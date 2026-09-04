<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Enums\ReminderSourceEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class Reminder
 *
 * The rule. Section 10.1 takes the word "reminder" apart into four things that
 * behave differently, and this is the second of them: the source is where the
 * need comes from, the rule is the logic, the occurrence is one concrete
 * deadline and the notification is what reaches somebody. The rule is the only
 * one of the four somebody configures; the other three follow from it.
 *
 * It hangs off the value it reads rather than off the pair of record and field.
 * That is what caps it at one live rule per value, and it is what makes a date
 * inside a repetition work with nothing added: each repetition's answer is its
 * own row, so each can carry its own rule.
 *
 * The zone is frozen when the rule is made. "Every year at 9am" means 9am where
 * somebody was when they asked for it, and moving away later must not shift a
 * reminder already set.
 *
 * Two axes, not one. `source` says where the date comes from and `cadence` says
 * how often the rule fires, because section 10.2 requires a date driven rule to
 * be free to fire once or to come round again.
 *
 * A rule that stops applying is deactivated, says why and keeps the way back
 * open. Unticking the box is a soft delete for the same reason: nothing here
 * disappears without an explanation.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property string $remindable_type
 * @property int $remindable_id
 * @property int $field_id
 * @property int|null $field_value_id
 * @property string $name
 * @property ReminderSourceEnum $source
 * @property ReminderCadenceEnum $cadence
 * @property int $frequency_interval
 * @property array<array-key, mixed> $moments
 * @property string $time_of_day
 * @property string $timezone
 * @property int $schedule_revision
 * @property bool $is_active
 * @property ReminderInactiveReasonEnum|null $inactive_reason
 * @property CarbonImmutable|null $deactivated_at
 * @property int|null $created_by_user_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read Contact|Entity $remindable
 * @property-read Field $field
 * @property-read FieldValue|null $fieldValue
 * @property-read User|null $createdBy
 * @property-read Collection<int, ReminderOccurrence> $occurrences
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'remindable_type',
    'remindable_id',
    'field_id',
    'field_value_id',
    'name',
    'source',
    'cadence',
    'frequency_interval',
    'moments',
    'time_of_day',
    'timezone',
    'schedule_revision',
    'is_active',
    'inactive_reason',
    'deactivated_at',
    'created_by_user_id',
])]
class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;
    use SoftDeletes;

    /**
     * The fewest days, weeks or months a moment may name. One is the floor
     * because a moment of nothing is the day itself, which has its own
     * direction.
     *
     * There is no matching ceiling here, because how far a moment may reach
     * depends on what it counts in: {@see ReminderMomentUnitEnum::maximumAmount()}
     * carries it, and one number for all three units is what let "365 months
     * before" through.
     */
    public const int MINIMUM_AMOUNT = 1;

    /**
     * The most times over a cadence may be spaced out. "Every ninety-nine years"
     * is already absurd and is the point at which refusing costs nobody
     * anything.
     */
    public const int MAXIMUM_INTERVAL = 99;

    /**
     * The hour a rule nobody has set a time on fires at. Nine in the morning is
     * the hour somebody reads a reminder about a person rather than the hour
     * they are asleep, and every screen that offers a reminder opens on it.
     */
    public const string DEFAULT_TIME = '09:00';

    /**
     * The schedule revision a rule starts at.
     *
     * The column has the same default, and a row read back from the database
     * would carry it either way. It is declared here as well so that a rule
     * still in memory after being created already knows its revision, since the
     * generation key of every deadline it produces is built from it.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'schedule_revision' => 1,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'source' => ReminderSourceEnum::class,
            'cadence' => ReminderCadenceEnum::class,
            'frequency_interval' => 'integer',
            'schedule_revision' => 'integer',
            'moments' => 'array',
            'is_active' => 'boolean',
            'inactive_reason' => ReminderInactiveReasonEnum::class,
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * Get the account the rule belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault the rule lives in.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the record the rule is about, which is a contact today and may be an
     * entity once entities have screens.
     *
     * @return MorphTo<Model, $this>
     */
    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the field the rule reads.
     *
     * @return BelongsTo<Field, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * Get the exact value the rule reads, which is null once somebody has
     * cleared it. Clearing a value destroys its row, and a rule that vanished
     * with it would be the silent deletion section 3.3 forbids, so the pointer
     * is let go of and the rule stays, quiet and saying why.
     *
     * @return BelongsTo<FieldValue, $this>
     */
    public function fieldValue(): BelongsTo
    {
        return $this->belongsTo(FieldValue::class);
    }

    /**
     * Get the user who created the rule.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the concrete deadlines the rule has produced.
     *
     * One cycle produces one of these for every moment, so a yearly rule with
     * two moments leaves two rows a year rather than one with two notifications
     * hanging off it. That is what makes putting one off touch only that one.
     *
     * @return HasMany<ReminderOccurrence, $this>
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(ReminderOccurrence::class);
    }

    /**
     * Scope the query to the rules still producing something.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope the query to the rules that have gone quiet, which a screen shows
     * and explains rather than hides.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeInactive(Builder $query): void
    {
        $query->where('is_active', false);
    }
}
