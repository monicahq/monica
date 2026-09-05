<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum ContactCategoryEnum: string implements SuppliedValueListItem
{
    case Family = 'family';
    case CloseFriend = 'close_friend';
    case Friend = 'friend';
    case Colleague = 'colleague';
    case Acquaintance = 'acquaintance';
    case Professional = 'professional';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Family => 'Family',
            self::CloseFriend => 'Close friend',
            self::Friend => 'Friend',
            self::Colleague => 'Colleague',
            self::Acquaintance => 'Acquaintance',
            self::Professional => 'Professional',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
