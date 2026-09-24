<?php

declare(strict_types=1);

namespace App\Enums;

enum DatePrecisionEnum: string
{
    case Day = 'day';
    case Month = 'month';
    case Year = 'year';
    case Decade = 'decade';
    case Unknown = 'unknown';
}
