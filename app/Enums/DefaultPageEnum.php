<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum DefaultPageEnum: string implements Translatable
{
    case General = 'general';
    case WaysToConnect = 'ways-to-connect';
    case Information = 'information';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::WaysToConnect => 'Ways to connect',
            self::Information => 'Information',
        };
    }

    /** @return list<DefaultSectionEnum> */
    public function sections(): array
    {
        return match ($this) {
            self::General => [
                DefaultSectionEnum::PersonalDetails,
                DefaultSectionEnum::Notes,
            ],
            self::WaysToConnect => [
                DefaultSectionEnum::ContactInformation,
                DefaultSectionEnum::Addresses,
            ],
            self::Information => [
                DefaultSectionEnum::Documents,
                DefaultSectionEnum::Photos,
            ],
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
