<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\ReminderOccurrenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * One concrete deadline: "5 May 2027 at 9am".
 *
 * It is the third of the four things section 10.1 separates, and the one
 * somebody actually acts on. A rule with two moments produces two of these in
 * every cycle, not one with two notifications hanging off it, and that is what
 * makes "put this one off" mean something: postponing the 5 May deadline leaves
 * the 12 May deadline of the same cycle, every deadline of 2028, and the 12 May
 * the source holds, exactly where they were.
 *
 * The moment is copied onto the row rather than looked up. It keeps the history
 * readable after somebody changes the rule, and it tells two moments apart even
 * when the calendar clamps them onto the same day.
 *
 * Two instants, not one. `original_scheduled_for` is what generation computed
 * and never moves; `scheduled_for` is what the deadline is due at now. They
 * start equal and part company the first time somebody puts the deadline off.
 *
 * `generation_key` is what makes generating twice harmless. It is built from the
 * rule's schedule revision, the cycle's local date and the normalised moment, so
 * the same deadline computed again is the same key, and a unique index on the
 * pair refuses the second write. The revision is in it because a rule changed and
 * changed back would otherwise produce keys identical to rows already cancelled,
 * and the rule would silently stop producing anything.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $reminder_id
 * @property CarbonImmutable $source_local_date
 * @property ReminderMomentDirectionEnum $moment_direction
 * @property int|null $moment_amount
 * @property ReminderMomentUnitEnum|null $moment_unit
 * @property string $generation_key
 * @property CarbonImmutable $scheduled_for
 * @property CarbonImmutable $original_scheduled_for
 * @property CarbonImmutable $local_date
 * @property ReminderOccurrenceStatusEnum $status
 * @property CarbonImmutable|null $postponed_to
 * @property int $postponed_count
 * @property CarbonImmutable|null $notified_at
 * @property CarbonImmutable|null $dismissed_at
 * @property CarbonImmutable|null $cancelled_at
 * @property ReminderOccurrenceStatusEnum|null $status_before_cancellation
 * @property ReminderOccurrenceCancelledReasonEnum|null $cancelled_reason
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read Reminder|null $reminder
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'vault_id',
    'reminder_id',
    'source_local_date',
    'moment_direction',
    'moment_amount',
    'moment_unit',
    'generation_key',
    'scheduled_for',
    'original_scheduled_for',
    'local_date',
    'status',
    'postponed_to',
    'postponed_count',
    'notified_at',
    'dismissed_at',
    'cancelled_at',
    'status_before_cancellation',
    'cancelled_reason',
])]
class ReminderOccurrence extends Model
{
    /** @use HasFactory<ReminderOccurrenceFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInVault;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'source_local_date' => 'immutable_date',
            'moment_direction' => ReminderMomentDirectionEnum::class,
            'moment_amount' => 'integer',
            'moment_unit' => ReminderMomentUnitEnum::class,
            'scheduled_for' => 'immutable_datetime',
            'original_scheduled_for' => 'immutable_datetime',
            'local_date' => 'immutable_date',
            'status' => ReminderOccurrenceStatusEnum::class,
            'postponed_to' => 'immutable_datetime',
            'postponed_count' => 'integer',
            'notified_at' => 'immutable_datetime',
            'dismissed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'status_before_cancellation' => ReminderOccurrenceStatusEnum::class,
            'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::class,
        ];
    }

    /**
     * Get the account the deadline belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault the deadline belongs to.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the rule that produced the deadline.
     *
     * @return BelongsTo<Reminder, $this>
     */
    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }

    /**
     * Get everything sent about this deadline, which is one row per channel.
     *
     * @return HasMany<Notification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'notifiable_id')
            ->where('notifiable_type', self::class);
    }

    /**
     * Scope the query to the deadlines whose instant has arrived and which
     * nobody has finished with. A deadline somebody put off is included: moving
     * one is not being done with it.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeDue(Builder $query): void
    {
        $query
            ->whereIn('status', ReminderOccurrenceStatusEnum::awaitingDelivery())
            ->where('scheduled_for', '<=', now());
    }

    /**
     * Scope the query to the deadlines that have not happened yet, which are the
     * only ones any reconciliation is allowed to touch. What is behind is
     * history.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeStillAhead(Builder $query): void
    {
        $query->where('scheduled_for', '>', now());
    }

    /**
     * Whether somebody may still put this deadline off or dismiss it.
     */
    public function isActionable(): bool
    {
        return $this->status->isActionable();
    }

    /**
     * Whether anything should still be said about this deadline.
     *
     * It is asked twice, and deliberately so. Once when the notifications are
     * made, and again in the instant before one of them actually goes out,
     * because quiet hours can hold a notification for hours and the world moves
     * in between: a deadline due at 3am on a contact archived at 7am must not
     * arrive at 8am. Archiving is how somebody says they no longer want to hear
     * about a record, and a notification that ignored it would defeat the only
     * thing archiving does.
     */
    public function isStillWarranted(?CarbonImmutable $expectedScheduledFor = null): bool
    {
        if (! $this->isActionable()) {
            return false;
        }

        if ($expectedScheduledFor !== null && ! $this->scheduled_for->equalTo($expectedScheduledFor)) {
            return false;
        }

        $reminder = $this->reminder;

        /* A rule somebody took away is soft deleted, so the relation comes back
           empty rather than trashed, and there is nothing left to be reminded
           of either way. */
        if ($reminder === null || ! $reminder->is_active) {
            return false;
        }

        $record = $reminder->remindable;

        if ($record === null) {
            return false;
        }

        return $record->is_archived !== true;
    }
}
