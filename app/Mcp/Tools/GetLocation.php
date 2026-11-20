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

#[Description('Read one place of the account: its name, the place it sits inside, its emoji, its address, its full path, the address it falls back on and how many places it holds.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetLocation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.locations.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'location' => ['required', 'string'],
        ]);

        $location = $this->location($request, $validated['location']);

        return $this->answerThroughApi($request, $location->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()->description('The id of the place, as list-locations gives it.')->required(),
        ];
    }
}
