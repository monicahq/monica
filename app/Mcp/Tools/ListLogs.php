<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the entries of the account log, newest first, a page at a time: who did what, where it came from and from which IP.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListLogs extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.logs';

    public function handle(Request $request): Response
    {
        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many entries a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
