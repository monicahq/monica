<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum HobbyEnum: string implements SuppliedValueListItem
{
    case Reading = 'reading';
    case Music = 'music';
    case Cinema = 'cinema';
    case Sport = 'sport';
    case Cooking = 'cooking';
    case Travel = 'travel';
    case Gardening = 'gardening';
    case Photography = 'photography';
    case Art = 'art';
    case Games = 'games';
    case Hiking = 'hiking';
    case Crafts = 'crafts';
    case Dancing = 'dancing';
    case Theatre = 'theatre';
    case Volunteering = 'volunteering';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Reading => 'Reading',
            self::Music => 'Music',
            self::Cinema => 'Cinema',
            self::Sport => 'Sport',
            self::Cooking => 'Cooking',
            self::Travel => 'Travel',
            self::Gardening => 'Gardening',
            self::Photography => 'Photography',
            self::Art => 'Art',
            self::Games => 'Games',
            self::Hiking => 'Hiking',
            self::Crafts => 'Crafts',
            self::Dancing => 'Dancing',
            self::Theatre => 'Theatre',
            self::Volunteering => 'Volunteering',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
