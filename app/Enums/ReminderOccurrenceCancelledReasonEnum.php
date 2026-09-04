<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\ReminderOccurrence;

/**
 * Why a deadline was cancelled.
 *
 * Section 3.3 forbids silent deletion, and a deadline the product removed on
 * somebody's behalf is exactly the case it is about: it is cancelled rather than
 * deleted, it says which of seven things happened, and four of the seven can be
 * undone.
 *
 * The split between reversible and not is the whole point of the enum. Archiving
 * a record, deleting it, taking its field out of the structure and switching the
 * rule off are all things somebody can undo, and undoing one must bring back
 * exactly the deadlines it silenced and no others. The other three cannot be
 * undone, and deliberately so: when the date or the schedule changes, the
 * replacement rows are the deadlines the new schedule defines, and reviving the
 * old ones would fire reminders for a date nobody holds any more. Clearing the
 * value is the same case, since typing the date in again makes a new rule.
 *
 * The backing value is the `cancelled_reason` column of
 * {@see ReminderOccurrence}.
 */
enum ReminderOccurrenceCancelledReasonEnum: string
{
    case RuleDisabled = 'rule_disabled';
    case RecordArchived = 'record_archived';
    case RecordDeleted = 'record_deleted';
    case FieldRemoved = 'field_removed';
    case ValueCleared = 'value_cleared';
    case SourceChanged = 'source_changed';
    case RuleChanged = 'rule_changed';

    /**
     * Whether reversing the cause brings the deadline back.
     */
    public function isReversible(): bool
    {
        return in_array($this, [
            self::RuleDisabled,
            self::RecordArchived,
            self::RecordDeleted,
            self::FieldRemoved,
        ], true);
    }

    /**
     * The reason matching one of the causes that silences a whole rule, so a
     * rule going quiet and its deadlines being cancelled never disagree about
     * why.
     */
    public static function forInactiveReason(ReminderInactiveReasonEnum $reason): self
    {
        return match ($reason) {
            ReminderInactiveReasonEnum::ValueCleared => self::ValueCleared,
            ReminderInactiveReasonEnum::RecordArchived => self::RecordArchived,
            ReminderInactiveReasonEnum::RecordDeleted => self::RecordDeleted,
            ReminderInactiveReasonEnum::FieldRemoved => self::FieldRemoved,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
