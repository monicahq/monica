<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

enum TimeFormatEnum: string
{
    case TwentyFourHour = '24';
    case TwelveHour = '12';

    /**
     * What the format is called on the screen where somebody chooses it. The
     * sentence doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::TwentyFourHour => '24-hour',
            self::TwelveHour => '12-hour',
        };
    }

    /**
     * The same moment written both ways, shown beside the name so nobody has to
     * work out which one they mean. It is an hour of the afternoon on purpose,
     * since that is where the two formats differ.
     */
    public function example(): string
    {
        return $this->format(now()->setTime(14, 0));
    }

    public function format(CarbonInterface $time): string
    {
        return match ($this) {
            self::TwentyFourHour => $time->format('H:i'),
            self::TwelveHour => $time->format('g:i A'),
        };
    }
}
