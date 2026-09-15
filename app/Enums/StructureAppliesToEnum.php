<?php

declare(strict_types=1);

namespace App\Enums;

enum StructureAppliesToEnum: string
{
    case Contact = 'contact';
    case Entity = 'entity';
    case Activity = 'activity';

    public function needsEntityType(): bool
    {
        return $this === self::Entity;
    }
}
