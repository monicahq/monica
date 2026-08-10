<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;

/**
 * A handful of countries to start from rather than the two hundred that exist.
 * Nobody keeps contacts in every country, and a list of ten somebody adds to is
 * quicker to use than a list of two hundred they have to search.
 *
 * The key is the ISO 3166-1 code, so a country stays recognisable to the
 * product after somebody has renamed it to whatever they call it.
 */
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

    /**
     * The name of the country, which doubles as the translation key.
     */
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

    /**
     * The name this item is seeded with.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
