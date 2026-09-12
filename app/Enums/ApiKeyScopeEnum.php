<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum ApiKeyScopeEnum: string implements Translatable
{
    case SensitiveFields = 'sensitive-fields';

    public function label(): string
    {
        return match ($this) {
            self::SensitiveFields => 'Sensitive fields',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SensitiveFields => 'Leave this off unless a device needs them. An address book on a phone can be read by the other applications on it.',
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
        return [$this->label(), $this->description()];
    }
}
