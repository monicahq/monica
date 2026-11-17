<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum ChangelogEntryTypeEnum: string implements Translatable
{
    case Feature = 'feature';
    case Improvement = 'improvement';
    case Fix = 'fix';

    public function label(): string
    {
        return match ($this) {
            self::Feature => 'Feature',
            self::Improvement => 'Improvement',
            self::Fix => 'Fix',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Feature => 'success',
            self::Improvement => 'info',
            self::Fix => 'neutral',
        };
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
