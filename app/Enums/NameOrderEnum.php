<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;

enum NameOrderEnum: string implements Translatable
{
    case GivenFirst = 'given_first';
    case FamilyFirst = 'family_first';

    /**
     * What the order is called on the screen where somebody chooses it. The
     * sentence doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::GivenFirst => 'Given name first',
            self::FamilyFirst => 'Family name first',
        };
    }

    /**
     * A name written in this order. The two parts are joined by a space, which
     * is what every locale the interface is translated into expects.
     */
    public function format(string $firstName, string $lastName): string
    {
        return match ($this) {
            self::GivenFirst => trim("{$firstName} {$lastName}"),
            self::FamilyFirst => trim("{$lastName} {$firstName}"),
        };
    }

    /**
     * The name of this case, which the screen that offers the choice reads.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
