<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;

enum RelationshipCategoryEnum: string implements Translatable
{
    case Family = 'family';
    case Romantic = 'romantic';
    case Friendship = 'friendship';
    case Professional = 'professional';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Family => 'Family',
            self::Romantic => 'Romantic',
            self::Friendship => 'Friendship',
            self::Professional => 'Professional',
            self::Other => 'Other',
        };
    }

    /** @return list<string> */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
