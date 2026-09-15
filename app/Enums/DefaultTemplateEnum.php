<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum DefaultTemplateEnum: string implements Translatable
{
    case BasicContact = 'basic-contact';

    public function label(): string
    {
        return match ($this) {
            self::BasicContact => 'Basic contact',
        };
    }

    public function appliesTo(): StructureAppliesToEnum
    {
        return match ($this) {
            self::BasicContact => StructureAppliesToEnum::Contact,
        };
    }

    /** @return list<DefaultPageEnum> */
    public function pages(): array
    {
        return match ($this) {
            self::BasicContact => DefaultPageEnum::cases(),
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
