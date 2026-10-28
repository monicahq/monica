<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum AccountExportStatusEnum: string implements Translatable
{
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Preparing => 'Being prepared',
            self::Ready => 'Ready to download',
            self::Failed => 'Failed',
            self::Expired => 'Expired',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
