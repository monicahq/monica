<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReminderAnswerEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInVault;
use Carbon\CarbonImmutable;
use Database\Factories\ReminderOccurrenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * One concrete deadline: "12 May 2027".
 *
 * It is the third of the four things section 10.1 separates, and the one
 * somebody answers. Every date a rule produces is one of these and nothing
 * else: there are no moments ahead of the day, so a yearly birthday produces one
 * row a year, and answering this year's leaves next year's exactly as it was.
 *
 * The answer lives here, never on the rule or the contact, because it is a fact
 * about one date. `answer` is null until somebody says they remembered or forgot,
 * and once written it never changes. It is kept apart from the two other things
 * that can happen to a deadline: its notifications going out, which never counts
 * as remembering, and its cancellation, which never counts as forgetting.
 *
 * A deadline is active while it has neither an answer nor a cancellation. Which
 * group it sits in, upcoming, today or overdue, is worked out from `local_date`
 * and today each time it is read, so it never goes stale at midnight.
 *
 * `generation_key` is what makes generating twice harmless. It is built from the
 * rule's schedule revision and the local date, so the same deadline computed
 * again is the same key, and a unique index on the pair refuses the second
 * write. The revision is in it because a rule changed and changed back would
 * otherwise produce keys identical to rows already cancelled, and the rule would
 * silently stop producing anything.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property int $reminder_id
 * @property CarbonImmutable $local_date
 * @property string $generation_key
 * @property CarbonImmutable $scheduled_for
 * @property CarbonImmutable|null $notifications_created_at
 * @property CarbonImmutable|null $notified_at
 * @property ReminderAnswerEnum|null $answer
 * @property CarbonImmutable|null $answered_at
 * @property CarbonImmutable|null $cancelled_at
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
    'local_date',
    'generation_key',
    'scheduled_for',
    'notifications_created_at',
    'notified_at',
    'answer',
    'answered_at',
    'cancelled_at',
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
            'local_date' => 'immutable_date',
            'scheduled_for' => 'immutable_datetime',
            'notifications_created_at' => 'immutable_datetime',
            'notified_at' => 'immutable_datetime',
            'answer' => ReminderAnswerEnum::class,
            'answered_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
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
     * Store the deadline's day as a day and nothing else, whatever the driver. Every query compares
     * it with a day written as a year, a month and a day, and a stored time of midnight would sort
     * after that string on a driver that keeps dates as text.
     *
     * @return Attribute<never, string>
     */
    protected function localDate(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): string => CarbonImmutable::parse($value)->format('Y-m-d'),
        );
    }

    /**
     * Scope the query to the deadlines still waiting for somebody: no answer and
     * no cancellation.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query
            ->whereNull('answer')
            ->whereNull('cancelled_at');
    }

    /**
     * Scope the query to the deadlines somebody answered.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeAnswered(Builder $query): void
    {
        $query->whereNotNull('answer');
    }

    /**
     * Scope the query to the active deadlines whose instant has arrived and for
     * which delivery has not made its notifications yet. Once it has, the
     * deadline is never swept again, which is what keeps it to one notification
     * per channel with no reminder after that.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeDueForNotification(Builder $query): void
    {
        $query
            ->active()
            ->whereNull('notifications_created_at')
            ->where('scheduled_for', '<=', now());
    }

    /**
     * Scope the query to what one vault is still waiting on, in date order.
     *
     * There is no lower bound: a deadline whose day has passed without an answer
     * is the first thing somebody opening the screen needs to see. The upper
     * bound is the caller's, because it is a day the model has no business
     * choosing. It is written as before the next day rather than with
     * whereDate, so the vault and day index answers it, and a day stored with a
     * midnight time on SQLite still falls inside it.
     *
     * A rule that has gone quiet or been taken away cancels its deadlines anyway.
     * Asking after the rule as well is what keeps the screen right in the moment
     * between the two.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeAwaitingAnswerInVault(Builder $query, Vault $vault, CarbonImmutable $untilDay): void
    {
        $query
            ->where('vault_id', $vault->id)
            ->where('account_id', $vault->account_id)
            ->active()
            ->where('local_date', '<', $untilDay->addDay()->toDateString())
            ->whereHas('reminder', function (Builder $rule): void {
                $rule->where('is_active', true);
            })
            ->orderBy('local_date')
            ->orderBy('scheduled_for');
    }

    /**
     * Scope the query to what one vault has already sent, most recent first.
     *
     * It asks nothing about the rule on purpose. A reminder that reached somebody
     * really reached them, and a rule deleted afterwards does not unsend it, so
     * joining the rule here would erase history the screen exists to show.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeSentInVault(Builder $query, Vault $vault): void
    {
        $query
            ->where('vault_id', $vault->id)
            ->where('account_id', $vault->account_id)
            ->whereNotNull('notified_at')
            ->orderByDesc('notified_at');
    }

    /**
     * Scope the query to what one vault has answered, the most recent answer first.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeAnsweredInVault(Builder $query, Vault $vault): void
    {
        $query
            ->where('vault_id', $vault->id)
            ->where('account_id', $vault->account_id)
            ->answered()
            ->orderByDesc('answered_at');
    }

    /**
     * Whether the deadline is still waiting for an answer.
     */
    public function isActive(): bool
    {
        return $this->answer === null && $this->cancelled_at === null;
    }

    /**
     * Whether anything should still be said about this deadline.
     *
     * It is asked twice, and deliberately so. Once when the notifications are
     * made, and again in the instant before one of them actually goes out,
     * because quiet hours can hold a notification into the next morning and the
     * world moves in between. A deadline somebody answered in the meantime needs
     * no notification, and one on a contact archived in the meantime must not
     * arrive either: archiving is how somebody says they no longer want to hear
     * about a record. A group cannot be archived; a deleted one has stopped
     * its rules already.
     */
    public function isStillWarranted(): bool
    {
        if (! $this->isActive()) {
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

        if ($record instanceof Group) {
            return true;
        }

        return $record->is_archived !== true;
    }
}
