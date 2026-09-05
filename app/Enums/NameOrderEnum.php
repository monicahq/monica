<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum NameOrderEnum: string implements Translatable
{
    case GivenFirst = 'given_first';
    case FamilyFirst = 'family_first';

    public function label(): string
    {
        return match ($this) {
            self::GivenFirst => 'Given name first',
            self::FamilyFirst => 'Family name first',
        };
    }

    public function format(string $firstName, string $lastName): string
    {
        return match ($this) {
            self::GivenFirst => trim("{$firstName} {$lastName}"),
            self::FamilyFirst => trim("{$lastName} {$firstName}"),
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
