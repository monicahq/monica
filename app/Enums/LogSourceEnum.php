<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where an action came from. A change made by hand and one made by an
 * automation are both legitimate, and the log has to be able to tell them
 * apart.
 */
enum LogSourceEnum: string
{
    case Web = 'web';
    case Api = 'api';
    case Mcp = 'mcp';
    case Import = 'import';
    case Automation = 'automation';
    case Module = 'module';
    case System = 'system';
}
