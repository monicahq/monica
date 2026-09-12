<?php

declare(strict_types=1);

namespace App\Enums;

enum LogSourceEnum: string
{
    case Web = 'web';
    case Api = 'api';
    case Dav = 'dav';
    case Mcp = 'mcp';
    case Import = 'import';
    case Automation = 'automation';
    case Module = 'module';
    case System = 'system';
}
