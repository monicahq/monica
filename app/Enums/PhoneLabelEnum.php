<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;

enum PhoneLabelEnum: string implements SuppliedValueListItem
{
    case Mobile = 'mobile';
    case Home = 'home';
    case Work = 'work';
    case Fax = 'fax';
    case Other = 'other';

    /**
     * What the number is for, shown beside it on a record. The word doubles as
     * the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::Mobile => 'Mobile',
            self::Home => 'Home',
            self::Work => 'Work',
            self::Fax => 'Fax',
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
