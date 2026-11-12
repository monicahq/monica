<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionValuationConfidenceEnum: string implements Translatable
{
    case Unknown = 'unknown';
    case Certain = 'certain';
    case Probable = 'probable';
    case Uncertain = 'uncertain';

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Unknown',
            self::Certain => 'Certain',
            self::Probable => 'Probable',
            self::Uncertain => 'Uncertain',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
