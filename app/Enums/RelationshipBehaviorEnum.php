<?php

declare(strict_types=1);

namespace App\Enums;

enum RelationshipBehaviorEnum: string
{
    case GenealogyParentage = 'genealogy_parentage';
    case Romantic = 'romantic';
}
