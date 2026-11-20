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

#[Description('Read one entry of the account log.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetLog extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.logs.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'log' => ['required', 'string'],
        ]);

        return $this->answerThroughApi($request, $this->log($request, $validated['log'])->id);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'log' => $schema->string()->description('The id of the entry, as list-logs gives it.')->required(),
        ];
    }
}
