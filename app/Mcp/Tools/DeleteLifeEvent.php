<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyLifeEvent;
use App\Models\Contact;
use App\Models\LifeEventParticipant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Take a life event off the timeline of everybody who was there. restore-life-event puts it back. The change is written to the account journal.')]
#[IsDestructive]
class DeleteLifeEvent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEvents.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'life_event' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $lifeEvent = $this->lifeEvent($vault, $validated['life_event']);

        $subject = $lifeEvent->participants
            ->map(fn (LifeEventParticipant $participation): mixed => $participation->participant)
            ->first(fn (mixed $person): bool => $person instanceof Contact);

        if (! $subject instanceof Contact) {
            return Response::error(__('Nobody who took part in this life event is left, so it cannot be changed here.'));
        }

        new DestroyLifeEvent(
            user: $this->user($request),
            vault: $vault,
            contact: $subject,
            lifeEvent: $lifeEvent,
        )->execute();

        return Response::text(__('The life event is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the life event.')->required(),
            'life_event' => $schema->string()->description('The id of the life event, as list-life-events gives it.')->required(),
        ];
    }
}
