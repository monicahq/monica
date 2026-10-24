<?php

declare(strict_types=1);

namespace Tests\Fixtures\Mcp\Servers;

use App\Mcp\Servers\EricaServer;
use Tests\Fixtures\Mcp\Tools\BreakUnexpectedly;
use Tests\Fixtures\Mcp\Tools\ReadOwnCallHistory;

class ServerWithFixtureTools extends EricaServer
{
    protected array $tools = [
        ReadOwnCallHistory::class,
        BreakUnexpectedly::class,
    ];
}
