<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * What actually reaches somebody, on one channel.
 *
 * It is the fourth of the four things section 10.1 separates, and deliberately
 * not limited to reminders. A marketplace author publishing a new version of an
 * installed structure, and a webhook subscription switched off after repeated
 * failures, are both things the product has to say, and they should arrive where
 * everything else arrives rather than through mechanisms of their own.
 *
 * A deadline is told once per channel, and a notification copies its deadline's
 * instant and never shifts it.
 *
 * `scheduled_for` and `held_until` are two different facts and are never
 * conflated. The first is when the thing was due; the second is the first moment
 * quiet hours allow it out. They are equal when nothing holds it, which is what
 * lets the sweep be a single comparison. A delivery in progress also pushes
 * `held_until` forward as its claim, so the row is not marked sent before it is.
 *
 * A delivery that failed keeps its row and its reason, because a notification
 * silently dropped is indistinguishable from one that was never scheduled.
 * Nothing is removed here by anything the account does. What does remove a row
 * is age: past `monica.notification_retention_months` it is pruned, on the
 * daily `model:prune` the application already runs. That is a stated retention
 * policy rather than a silent loss, and the period is an instance setting.
 *
 * Pruning takes the per channel detail with it. What the deadline itself
 * records, `reminder_occurrences.notified_at`, is untouched, so the history
 * still says somebody was told and says plainly that it can no longer say on
 * which channel.
 *
 * The name is `App\Models\Notification` and has nothing to do with Laravel's own
 * notification system, which lives in `app/Notifications` and is used for the
 * framework's email verification.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $user_id
 * @property NotificationTypeEnum $type
 * @property NotificationChannelEnum $channel
 * @property string|null $notifiable_type
 * @property int|null $notifiable_id
 * @property string $title
 * @property string $body
 * @property array<array-key, mixed>|null $payload
 * @property string $locale
 * @property CarbonImmutable $scheduled_for
 * @property CarbonImmutable $held_until
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable|null $failed_at
 * @property string|null $failure_reason
 * @property int $attempts
 * @property CarbonImmutable|null $read_at
 * @property int|null $digest_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read User $user
 * @property-read ReminderOccurrence|null $notifiable
 * @property-read Notification|null $digest
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'user_id',
    'type',
    'channel',
    'notifiable_type',
    'notifiable_id',
    'title',
    'body',
    'payload',
    'locale',
    'scheduled_for',
    'held_until',
    'sent_at',
    'delivered_at',
    'failed_at',
    'failure_reason',
    'attempts',
    'read_at',
    'digest_id',
])]
class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    use HasUuids;
    use Prunable;
    use ResolvesModelInAccount;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'type' => NotificationTypeEnum::class,
            'channel' => NotificationChannelEnum::class,
            'payload' => 'array',
            'scheduled_for' => 'immutable_datetime',
            'held_until' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
            'attempts' => 'integer',
            'read_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the account the notification belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user it is for.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get what the notification is about, which is a reminder deadline today and
     * is null on a digest, since a summary is about several things at once.
     *
     * @return MorphTo<Model, $this>
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the summary this notification was folded into.
     *
     * @return BelongsTo<Notification, $this>
     */
    public function digest(): BelongsTo
    {
        return $this->belongsTo(self::class, 'digest_id');
    }

    /**
     * Get the notifications this summary carried.
     *
     * @return HasMany<Notification, $this>
     */
    public function folded(): HasMany
    {
        return $this->hasMany(self::class, 'digest_id');
    }

    /**
     * The notifications old enough to be removed under the retention policy.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where(
            'created_at',
            '<',
            now()->subMonths(max(1, (int) config('monica.notification_retention_months'))),
        );
    }

    /**
     * Scope the query to what may go out now: nothing already sent, nothing
     * abandoned, nothing already folded into a summary, and nothing quiet hours
     * are still holding.
     *
     * It says nothing about whether the account wants its notifications one at a
     * time or folded, because that is a fact about the account rather than about
     * the row. The two sweeps add that filter themselves.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeDeliverable(Builder $query): void
    {
        $query
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->whereNull('digest_id')
            ->where('held_until', '<=', now());
    }
}
