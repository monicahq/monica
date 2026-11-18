<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionStatusEnum: string implements Translatable
{
    case Owned = 'owned';
    case Ordered = 'ordered';
    case Sold = 'sold';
    case GivenAway = 'given_away';
    case Lost = 'lost';
    case Stolen = 'stolen';
    case ThrownAway = 'thrown_away';

    public function label(): string
    {
        return match ($this) {
            self::Owned => 'Owned',
            self::Ordered => 'Ordered',
            self::Sold => 'Sold',
            self::GivenAway => 'Given away',
            self::Lost => 'Lost',
            self::Stolen => 'Stolen',
            self::ThrownAway => 'Thrown away',
        };
    }

    public function isNoLongerHeld(): bool
    {
        return match ($this) {
            self::Sold, self::GivenAway, self::ThrownAway, self::Lost, self::Stolen => true,
            self::Owned, self::Ordered => false,
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
