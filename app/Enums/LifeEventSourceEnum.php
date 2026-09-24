<?php

declare(strict_types=1);

namespace App\Enums;

enum LifeEventSourceEnum: string
{
    case Day = 'day';
    case Contact = 'contact';
    case Api = 'api';
    case Mcp = 'mcp';
    case Import = 'import';
    case Module = 'module';
}
