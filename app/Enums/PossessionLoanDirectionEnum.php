<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionLoanDirectionEnum: string implements Translatable
{
    case Lent = 'lent';
    case Borrowed = 'borrowed';

    public function label(): string
    {
        return match ($this) {
            self::Lent => 'Lent out',
            self::Borrowed => 'Borrowed in',
        };
    }

    public function verb(): string
    {
        return match ($this) {
            self::Lent => 'Lent to',
            self::Borrowed => 'Borrowed from',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->verb()];
    }
}
