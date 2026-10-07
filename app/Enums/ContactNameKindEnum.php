<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum ContactNameKindEnum: string implements Translatable
{
    case Written = 'written';
    case Transliteration = 'transliteration';
    case Phonetic = 'phonetic';

    public function label(): string
    {
        return match ($this) {
            self::Written => 'Written form',
            self::Transliteration => 'Transliteration',
            self::Phonetic => 'Pronunciation',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
