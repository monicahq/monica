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

#[Description('Offer a kind of life event when somebody records one, or stop offering it. Life events already recorded with it stay. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class SetLifeEventTypeActivation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.activation.update';

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
            'is_active' => $schema->boolean()->description('True to offer it, false to stop offering it.')->required(),
        ];
    }
}
