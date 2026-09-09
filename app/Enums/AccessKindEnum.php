<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum AccessKindEnum: string implements Translatable
{
    case Lifetime = 'lifetime';
    case Subscription = 'subscription';
    case Granted = 'granted';

    public function label(): string
    {
        return match ($this) {
            self::Lifetime => 'Lifetime access',
            self::Subscription => 'Subscription',
            self::Granted => 'Granted by us',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
