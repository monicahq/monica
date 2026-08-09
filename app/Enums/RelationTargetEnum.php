<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a relation field points at. Pointing at an entity says nothing on its
 * own, since an entity is whatever the account invented, so a field that points
 * at one also names the type.
 */
enum RelationTargetEnum: string
{
    case Contact = 'contact';
    case Entity = 'entity';
}
