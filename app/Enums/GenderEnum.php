<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum GenderEnum: string implements SuppliedValueListItem
{
    case Woman = 'woman';
    case Man = 'man';
    case NonBinary = 'non_binary';
    case PreferNotToSay = 'prefer_not_to_say';
    case Other = 'other';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Woman => 'Woman',
            self::Man => 'Man',
            self::NonBinary => 'Non-binary',
            self::PreferNotToSay => 'Prefer not to say',
            self::Other => 'Other',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
