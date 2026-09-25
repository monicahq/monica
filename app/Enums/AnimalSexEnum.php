<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum AnimalSexEnum: string implements SuppliedValueListItem
{
    case Female = 'female';
    case Male = 'male';
    case Unknown = 'unknown';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Female => 'Female',
            self::Male => 'Male',
            self::Unknown => 'Unknown',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
