<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateLifeEventTypePosition;
use App\Http\Resources\LifeEventTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a kind of life event to another place within its category. The change is written to the account journal.')]
class SetLifeEventTypePosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.position.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_type' => ['required', 'string'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $type = $this->lifeEventType($request, $validated['life_event_type']);

        new UpdateLifeEventTypePosition(
            user: $this->user($request),
            lifeEventType: $type,
            position: (int) $validated['position'],
        )->execute();

        return Response::json(new LifeEventTypeResource($type->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_type' => $schema->string()->description('The identifier of the life event type, as list-life-event-types gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes within its category, counting from 1.')->required(),
        ];
    }
}
