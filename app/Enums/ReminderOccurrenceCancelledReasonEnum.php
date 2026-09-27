<?php

declare(strict_types=1);

namespace App\Enums;

enum ReminderOccurrenceCancelledReasonEnum: string
{
    case RuleDeleted = 'rule_deleted';
    case RecordArchived = 'record_archived';
    case RecordDeleted = 'record_deleted';
    case FieldRemoved = 'field_removed';
    case ValueCleared = 'value_cleared';
    case SourceChanged = 'source_changed';
    case RuleChanged = 'rule_changed';

    public function isReversible(): bool
    {
        return in_array($this, [
            self::RuleDeleted,
            self::RecordArchived,
            self::RecordDeleted,
            self::FieldRemoved,
        ], true);
    }

    public static function forInactiveReason(ReminderInactiveReasonEnum $reason): self
    {
        return match ($reason) {
            ReminderInactiveReasonEnum::ValueCleared => self::ValueCleared,
            ReminderInactiveReasonEnum::RecordArchived => self::RecordArchived,
            ReminderInactiveReasonEnum::RecordDeleted => self::RecordDeleted,
            ReminderInactiveReasonEnum::FieldRemoved => self::FieldRemoved,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
