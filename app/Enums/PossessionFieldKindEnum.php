<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionFieldKindEnum: string implements Translatable
{
    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case Boolean = 'boolean';
    case Select = 'select';
    case Rating = 'rating';

    public const int MAX_RATING = 5;

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Boolean => 'Yes or no',
            self::Select => 'Choice from a list',
            self::Rating => 'Rating from one to five',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
