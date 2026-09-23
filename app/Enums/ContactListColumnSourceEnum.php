<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum ContactListColumnSourceEnum: string implements Translatable
{
    case Nickname = 'nickname';

    case Email = 'email';

    case Phone = 'phone';

    case Birthday = 'birthday';

    case Template = 'template';

    case LastSpoke = 'last-spoke';

    case Added = 'added';

    case Field = 'field';

    public function label(): string
    {
        return match ($this) {
            self::Nickname => 'Nickname',
            self::Email => 'Email',
            self::Phone => 'Phone number',
            self::Birthday => 'Birthday',
            self::Template => 'Template',
            self::LastSpoke => 'Last spoke',
            self::Added => 'Date added',
            self::Field => 'Template field',
        };
    }

    public function sort(): ?ContactSortEnum
    {
        return match ($this) {
            self::Nickname => ContactSortEnum::Nickname,
            self::LastSpoke => ContactSortEnum::LastSpoke,
            self::Added => ContactSortEnum::Added,
            default => null,
        };
    }

    /**
     * The columns a vault shows until somebody chooses its own.
     *
     * @return list<array{source: self, isVisible: bool}>
     */
    public static function defaults(): array
    {
        return [
            ['source' => self::Email, 'isVisible' => true],
            ['source' => self::Phone, 'isVisible' => true],
            ['source' => self::LastSpoke, 'isVisible' => true],
            ['source' => self::Template, 'isVisible' => false],
        ];
    }

    /** @return list<self> */
    public static function shared(): array
    {
        return array_values(array_filter(self::cases(), fn (self $source): bool => $source !== self::Field));
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
