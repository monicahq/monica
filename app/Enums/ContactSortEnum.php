<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

/**
 * The orders a list of contacts can be read in.
 *
 * The value is what the address carries and the column is kept apart from it, so
 * the address never names the schema. Both the people screen and the API offer
 * these four, and neither may invent a fifth on its own.
 */
enum ContactSortEnum: string implements Translatable
{
    case Name = 'name';

    case Nickname = 'nickname';

    case LastSpoke = 'last-spoke';

    case Added = 'added';

    /**
     * What the control offering this order is called.
     */
    public function label(): string
    {
        return match ($this) {
            self::Name => 'Name',
            self::Nickname => 'Nickname',
            self::LastSpoke => 'Last spoke',
            self::Added => 'Added',
        };
    }

    /**
     * The column the order reads.
     *
     * The name orders on `sort_key`, not `sort_name`: ordered as bytes, Élodie
     * lands after Z for a French reader. Comparing keys byte by byte gives that
     * language's order on a database that knows no language.
     */
    public function column(): string
    {
        return match ($this) {
            self::Name => 'sort_key',
            self::Nickname => 'nickname',
            self::LastSpoke => 'last_activity_at',
            self::Added => 'created_at',
        };
    }

    /**
     * Whether a contact may have nothing in this order's column, which is what
     * makes it necessary to say where the unknowns go.
     */
    public function canBeUnknown(): bool
    {
        return match ($this) {
            self::Nickname, self::LastSpoke => true,
            self::Name, self::Added => false,
        };
    }

    /**
     * Whether this order is about time, which decides whether its two directions
     * are called newest and oldest or A to Z.
     */
    public function isChronological(): bool
    {
        return match ($this) {
            self::LastSpoke, self::Added => true,
            self::Name, self::Nickname => false,
        };
    }

    /**
     * The order a value names, or by name when it names none.
     */
    public static function fromValueOrName(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Name;
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
