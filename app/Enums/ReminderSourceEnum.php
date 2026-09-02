<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Reminder;

/**
 * Where the need for a reminder comes from.
 *
 * Section 10.1 of the specification names four sources: a value entered on a
 * record, a manual entry, an activity, and a silence. Only the first has a
 * screen, so only the first is a case. A source somebody can already write into
 * the column is a promise the product cannot keep, which is the same reason
 * {@see FieldTypeEnum} carries only the types that render.
 *
 * It is deliberately a separate axis from {@see ReminderCadenceEnum}: the source
 * says where the date comes from, the cadence says how often the rule fires, and
 * section 10.2 requires that a field driven rule be free to do either.
 *
 * The backing value is the `source` column of {@see Reminder}.
 */
enum ReminderSourceEnum: string
{
    case FieldValue = 'field_value';
}
