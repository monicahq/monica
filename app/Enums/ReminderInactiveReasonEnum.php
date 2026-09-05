<?php

declare(strict_types=1);

namespace App\Enums;

use App\Actions\DeactivateReminders;
use App\Actions\ReactivateReminders;
use App\Models\Reminder;

/**
 * Why a rule has gone quiet.
 *
 * Section 3.3 forbids silent deletion, so a rule that stops applying is
 * deactivated, explains itself and keeps the way back open. The reason is what
 * the explanation is built from, and it is also what makes reactivation safe:
 * {@see ReactivateReminders} revives only the rules silenced by the cause being
 * reversed, so unarchiving a contact whose field has also gone leaves the rule
 * quiet and still saying why.
 *
 * Four of the five are things that happened to the rule. The fifth, somebody
 * switching it off by hand, is the only one a person sets and the only one a
 * person may lift, and it is what makes pausing different from removing:
 * unticking the box on the record soft deletes the rule, while pausing keeps it
 * whole and keeps its history.
 *
 * The backing value is the `inactive_reason` column of {@see Reminder}, written
 * only by {@see DeactivateReminders}.
 */
enum ReminderInactiveReasonEnum: string
{
    case ValueCleared = 'value_cleared';
    case RecordArchived = 'record_archived';
    case RecordDeleted = 'record_deleted';
    case FieldRemoved = 'field_removed';
    case PausedByUser = 'paused_by_user';

    /**
     * Whether the person looking at the rule is the one who may lift the reason.
     *
     * Only what somebody chose is theirs to undo. A rule quiet because its
     * record is archived comes back by unarchiving the record, so a screen that
     * offered to reactivate it would be offering something that cannot be done
     * from there.
     */
    public function isLiftedByUser(): bool
    {
        return $this === self::PausedByUser;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
