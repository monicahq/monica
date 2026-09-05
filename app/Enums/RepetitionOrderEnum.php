<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum RepetitionOrderEnum: string implements Translatable
{
    case NewestLast = 'newest_last';
    case NewestFirst = 'newest_first';

    public function label(): string
    {
        return match ($this) {
            self::NewestLast => 'New ones at the bottom',
            self::NewestFirst => 'New ones at the top',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NewestLast => 'The list grows downwards, which suits a collection somebody builds up.',
            self::NewestFirst => 'The newest is first, which suits something read latest first.',
        };
    }

    public function addsToTheFront(): bool
    {
        return $this === self::NewestFirst;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->description()];
    }
}
