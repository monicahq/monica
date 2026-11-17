<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one kind of life event: its name, description, icon, colour, category, position, whether it is offered and whether it came with the account.')]
#[IsReadOnly]
class GetLifeEventType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_type' => ['required', 'string'],
        ]);

        $lifeEventType = $this->lifeEventType($request, $validated['life_event_type']);

        return $this->answerThroughApi($request, $lifeEventType->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_type' => $schema->string()->description('The id of the life event type, as list-life-event-types gives it.')->required(),
        ];
    }
}
