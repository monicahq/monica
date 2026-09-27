<?php

declare(strict_types=1);

namespace App\Enums;

enum ReminderSourceEnum: string
{
    case Manual = 'manual';
    case FieldValue = 'field_value';
}
