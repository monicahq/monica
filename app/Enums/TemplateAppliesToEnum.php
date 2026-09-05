<?php

declare(strict_types=1);

namespace App\Enums;

enum TemplateAppliesToEnum: string
{
    case Contact = 'contact';
    case Entity = 'entity';

    public function needsEntityType(): bool
    {
        return $this === self::Entity;
    }
}
