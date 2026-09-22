<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum EmailLabelEnum: string implements SuppliedValueListItem
{
    case Home = 'home';
    case Personal = 'personal';
    case Work = 'work';
    case Other = 'other';
    case Custom = 'custom...';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal',
            self::Work => 'Work',
            self::Home => 'Home',
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
