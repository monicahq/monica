<?php

declare(strict_types=1);

namespace App\Enums;

enum RelationTargetEnum: string
{
    case Contact = 'contact';
    case Entity = 'entity';
}
