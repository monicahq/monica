<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkModeEnum: string
{
    case FullyRemote = 'fully_remote';
    case Hybrid = 'hybrid';
    case OfficeBased = 'office_based';
}
