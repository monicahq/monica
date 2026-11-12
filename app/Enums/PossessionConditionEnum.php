<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionConditionEnum: string implements Translatable
{
    case New = 'new';
    case LikeNew = 'like_new';
    case Used = 'used';
    case Worn = 'worn';
    case Damaged = 'damaged';
    case Broken = 'broken';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::LikeNew => 'Like new',
            self::Used => 'Used',
            self::Worn => 'Worn',
            self::Damaged => 'Damaged',
            self::Broken => 'Broken',
        };
    }

    public function colour(): VaultColourEnum
    {
        return match ($this) {
            self::New => VaultColourEnum::Green,
            self::LikeNew => VaultColourEnum::Teal,
            self::Used => VaultColourEnum::Blue,
            self::Worn => VaultColourEnum::Amber,
            self::Damaged => VaultColourEnum::Rust,
            self::Broken => VaultColourEnum::Slate,
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
