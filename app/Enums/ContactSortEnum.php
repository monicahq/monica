<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum ContactSortEnum: string implements Translatable
{
    case Name = 'name';

    case Nickname = 'nickname';

    case LastSpoke = 'last-spoke';

    case Added = 'added';

    public function label(): string
    {
        return match ($this) {
            self::Name => 'Name',
            self::Nickname => 'Nickname',
            self::LastSpoke => 'Last spoke',
            self::Added => 'Added',
        };
    }

    public function column(): string
    {
        return match ($this) {
            self::Name => 'sort_key',
            self::Nickname => 'nickname',
            self::LastSpoke => 'last_interaction_at',
            self::Added => 'created_at',
        };
    }

    public function canBeUnknown(): bool
    {
        return match ($this) {
            self::Nickname, self::LastSpoke => true,
            self::Name, self::Added => false,
        };
    }

    public function isChronological(): bool
    {
        return match ($this) {
            self::LastSpoke, self::Added => true,
            self::Name, self::Nickname => false,
        };
    }

    public static function fromValueOrName(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Name;
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
