<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum DefaultLocationEnum: string implements Translatable
{
    case House = 'house';
    case LivingRoom = 'living_room';
    case Kitchen = 'kitchen';
    case Bedroom = 'bedroom';
    case Bathroom = 'bathroom';
    case StorageSpace = 'storage_space';

    public function label(): string
    {
        return match ($this) {
            self::House => 'My house',
            self::LivingRoom => 'Living room',
            self::Kitchen => 'Kitchen',
            self::Bedroom => 'Bedroom',
            self::Bathroom => 'Bathroom',
            self::StorageSpace => 'Storage space',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::House => '🏠',
            self::LivingRoom => '🛋️',
            self::Kitchen => '🍳',
            self::Bedroom => '🛏️',
            self::Bathroom => '🚿',
            self::StorageSpace => '📦',
        };
    }

    public function isInsideHouse(): bool
    {
        return $this !== self::House;
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
