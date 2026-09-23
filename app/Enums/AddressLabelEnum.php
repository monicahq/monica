<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum AddressLabelEnum: string implements SuppliedValueListItem
{
    case Home = 'home';
    case Work = 'work';
    case HolidayHome = 'holiday_home';
    case Other = 'other';
    case Custom = 'custom...';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Home => 'Home',
            self::Work => 'Work',
            self::HolidayHome => 'Holiday home',
            self::Other => 'Other',
            self::Custom => 'Custom...',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
