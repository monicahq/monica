<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum PhoneLabelEnum: string implements SuppliedValueListItem
{
    case Mobile = 'mobile';
    case Home = 'home';
    case Work = 'work';
    case Fax = 'fax';
    case Other = 'other';
    case Custom = 'custom...';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Mobile => 'Mobile',
            self::Home => 'Home',
            self::Work => 'Work',
            self::Fax => 'Fax',
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
