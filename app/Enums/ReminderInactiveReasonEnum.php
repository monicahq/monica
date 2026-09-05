<?php

declare(strict_types=1);

namespace App\Enums;

enum ReminderInactiveReasonEnum: string
{
    case ValueCleared = 'value_cleared';
    case RecordArchived = 'record_archived';
    case RecordDeleted = 'record_deleted';
    case FieldRemoved = 'field_removed';
    case PausedByUser = 'paused_by_user';

    public function isLiftedByUser(): bool
    {
        return $this === self::PausedByUser;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
