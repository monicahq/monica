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
 * The specification's fifth reason, the owner switching a rule off by hand, has
 * no case yet. Unticking the box removes the rule instead, which is a soft
 * delete, so nothing today produces it.
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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
