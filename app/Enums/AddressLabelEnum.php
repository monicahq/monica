<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;

enum AddressLabelEnum: string implements SuppliedValueListItem
{
    case Home = 'home';
    case Work = 'work';
    case HolidayHome = 'holiday_home';
    case Previous = 'previous';
    case Other = 'other';

    /**
     * What the address is, shown beside it on a record. The word doubles as the
     * translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::Home => 'Home',
            self::Work => 'Work',
            self::HolidayHome => 'Holiday home',
            self::Previous => 'Previous',
            self::Other => 'Other',
        };
    }

    /**
     * The label this item is seeded with.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
