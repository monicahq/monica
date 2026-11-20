<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum NeuterStatusEnum: string implements SuppliedValueListItem
{
    case Yes = 'yes';
    case No = 'no';
    case Unknown = 'unknown';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Yes => 'Yes',
            self::No => 'No',
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
