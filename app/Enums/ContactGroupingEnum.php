<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum ContactGroupingEnum: string implements Translatable
{
    case Everyone = 'all';

    case Favourites = 'favourites';

    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Everyone => 'Everybody',
            self::Favourites => 'Favourites',
            self::Archived => 'Archived',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Everyone => 'people',
            self::Favourites => 'star',
            self::Archived => 'file',
        };
    }

    public static function fromValueOrEveryone(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Everyone;
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
        return [$this->label()];
    }
}
