<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\ActivateLifeEventType;
use App\Actions\DeactivateLifeEventType;
use App\Http\Resources\LifeEventTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Offer a kind of life event when somebody records one, or stop offering it. Life events already recorded with it stay. The change is written to the account journal.')]
class SetLifeEventTypeActivation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.activation.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_type' => ['required', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $type = $this->lifeEventType($request, $validated['life_event_type']);

        if ((bool) $validated['is_active'] === $type->is_active) {
            return Response::json(new LifeEventTypeResource($type)->resolve());
        }

        if ($validated['is_active']) {
            new ActivateLifeEventType(user: $this->user($request), lifeEventType: $type)->execute();
        }

        if (! $validated['is_active']) {
            new DeactivateLifeEventType(user: $this->user($request), lifeEventType: $type)->execute();
        }

        return Response::json(new LifeEventTypeResource($type->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_type' => $schema->string()->description('The identifier of the life event type, as list-life-event-types gives it.')->required(),
            'is_active' => $schema->boolean()->description('True to offer it, false to stop offering it.')->required(),
        ];
    }
}
