<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\ReminderOccurrence;

/**
 * What became of one deadline.
 *
 * `Pending` and `Postponed` are the two states delivery looks at, because a
 * deadline somebody moved is still a deadline waiting to happen. `Notified` says
 * the notification layer has dealt with the deadline, which is what stops the
 * sweep picking it up again for ever, including on an account that has asked for
 * silence and where there was nothing to send. Whether anything actually went
 * out is a different fact and lives in `notified_at`.
 *
 * `Dismissed` is terminal. It means somebody no longer wants to be interrupted
 * about this one deadline; it says nothing about whether an action was done.
 * It does not disable the rule, so the next cycle still happens.
 *
 * `Cancelled` is the only state the product enters on somebody's behalf, and it
 * never arrives without a {@see ReminderOccurrenceCancelledReasonEnum} saying
 * why. Some cancellations are reversed later, which is why the state held
 * beforehand is kept rather than guessed at.
 *
 * The backing value is the `status` column of {@see ReminderOccurrence}.
 */
enum ReminderOccurrenceStatusEnum: string
{
    case Pending = 'pending';
    case Notified = 'notified';
    case Postponed = 'postponed';
    case Dismissed = 'dismissed';
    case Cancelled = 'cancelled';

    /**
     * Whether somebody may still postpone or dismiss this deadline.
     */
    public function isActionable(): bool
    {
        return in_array($this, [self::Pending, self::Notified, self::Postponed], true);
    }

    /**
     * Whether the deadline is finished with, one way or another.
     */
    public function isTerminal(): bool
    {
        return ! $this->isActionable();
    }

    /**
     * Whether delivery should pick this deadline up when its instant arrives.
     * A postponed deadline is included: moving one is not the same as being
     * done with it.
     */
    public function awaitsDelivery(): bool
    {
        return in_array($this, [self::Pending, self::Postponed], true);
    }

    /**
     * The states delivery reads, as column values.
     *
     * @return list<string>
     */
    public static function awaitingDelivery(): array
    {
        return array_values(array_map(
            fn (self $case): string => $case->value,
            array_filter(self::cases(), fn (self $case): bool => $case->awaitsDelivery()),
        ));
    }

    /**
     * The states somebody may still act on, as column values.
     *
     * @return list<string>
     */
    public static function actionable(): array
    {
        return array_values(array_map(
            fn (self $case): string => $case->value,
            array_filter(self::cases(), fn (self $case): bool => $case->isActionable()),
        ));
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
