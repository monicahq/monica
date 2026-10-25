<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\RestoreLifeEvent as RestoreLifeEventAction;
use App\Http\Resources\LifeEventResource;
use App\Models\Contact;
use App\Models\LifeEventParticipant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Put a deleted life event back on the timeline of everybody who was there. The change is written to the account journal.')]
class RestoreLifeEvent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEvents.restoration.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'life_event' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $lifeEvent = $this->lifeEvent($vault, $validated['life_event'], withTrashed: true);

        if (! $lifeEvent->trashed()) {
            return Response::error(__('That life event is not deleted.'));
        }

        $subject = $lifeEvent->participants
            ->map(fn (LifeEventParticipant $participation): mixed => $participation->participant)
            ->first(fn (mixed $person): bool => $person instanceof Contact);

        if (! $subject instanceof Contact) {
            return Response::error(__('Nobody who took part in this life event is left, so it cannot be changed here.'));
        }

        new RestoreLifeEventAction(
            user: $this->user($request),
            vault: $vault,
            contact: $subject,
            lifeEvent: $lifeEvent,
        )->execute();

        $resource = new LifeEventResource($this->lifeEvent($vault, $lifeEvent->uuid));
        $resource->withSensitive = $this->reachesSensitiveFields($request);

        return Response::json($resource->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the life event.')->required(),
            'life_event' => $schema->string()->description('The id of the deleted life event.')->required(),
        ];
    }
}
