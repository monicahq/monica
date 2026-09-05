<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

/**
 * Which people a list of contacts is narrowed to.
 *
 * The vocabulary is here rather than on the screen that first needed it, because
 * the people screen and the API both offer these three and both have to mean the
 * same thing by them. The value is what the address carries, in a path segment
 * for the screen and in a query parameter for the API.
 *
 * Archived is a grouping of its own rather than an addition to the others: an
 * archived contact is out of every list but this one.
 */
enum ContactGroupingEnum: string implements Translatable
{
    case Everyone = 'all';

    case Favourites = 'favourites';

    case Archived = 'archived';

    /**
     * What the chip offering this grouping is called.
     */
    public function label(): string
    {
        return match ($this) {
            self::Everyone => 'Everyone',
            self::Favourites => 'Favourites',
            self::Archived => 'Archived',
        };
    }

    /**
     * The icon the chip carries beside its label.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Everyone => 'people',
            self::Favourites => 'star',
            self::Archived => 'file',
        };
    }

    /**
     * The grouping a value names, or everyone when it names none. A hand edited
     * address narrows the list to nothing at worst.
     */
    public static function fromValueOrEveryone(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Everyone;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
