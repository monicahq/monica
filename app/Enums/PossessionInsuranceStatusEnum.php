<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum PossessionInsuranceStatusEnum: string implements Translatable
{
    case Active = 'active';
    case Pending = 'pending';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Pending => 'Pending',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'warning',
            self::Expired, self::Cancelled => 'neutral',
        };
    }

    public function icon(): ?string
    {
        return match ($this) {
            self::Active => 'check',
            self::Cancelled => 'close',
            default => null,
        };
    }

    public function position(): int
    {
        return match ($this) {
            self::Active => 0,
            self::Pending => 1,
            self::Expired => 2,
            self::Cancelled => 3,
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
