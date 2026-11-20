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

#[Description('Look one life event up in a vault: its type, its date, what was written about it, who was there and the values of its custom fields, with the field ids update-life-event takes. The values of sensitive fields are given only to a key holding the sensitive fields scope.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetLifeEvent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEvents.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'life_event' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $lifeEvent = $this->lifeEvent($vault, $validated['life_event']);

        return $this->answerThroughApi($request, $vault->uuid, $lifeEvent->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'life_event' => $schema->string()->description('The id of the life event, as list-life-events gives it.')->required(),
        ];
    }
}
