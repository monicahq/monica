<?php

declare(strict_types=1);

namespace Tests\Fixtures\Mcp\Servers;

use App\Mcp\Servers\MonicaServer;
use Tests\Fixtures\Mcp\Tools\BreakUnexpectedly;
use Tests\Fixtures\Mcp\Tools\DeleteLocationAnsweringRaw;
use Tests\Fixtures\Mcp\Tools\ReadOwnCallHistory;

class ServerWithFixtureTools extends MonicaServer
{
    protected array $tools = [
        ReadOwnCallHistory::class,
        BreakUnexpectedly::class,
        DeleteLocationAnsweringRaw::class,
    ];
}
