<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum CountryEnum: string implements SuppliedValueListItem
{
    case UnitedStates = 'us';
    case UnitedKingdom = 'gb';
    case Canada = 'ca';
    case France = 'fr';
    case Germany = 'de';
    case Spain = 'es';
    case Italy = 'it';
    case Netherlands = 'nl';
    case Brazil = 'br';
    case Australia = 'au';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::UnitedStates => 'United States',
            self::UnitedKingdom => 'United Kingdom',
            self::Canada => 'Canada',
            self::France => 'France',
            self::Germany => 'Germany',
            self::Spain => 'Spain',
            self::Italy => 'Italy',
            self::Netherlands => 'Netherlands',
            self::Brazil => 'Brazil',
            self::Australia => 'Australia',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
