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

#[Description('Change a life event. The fields given replace its custom values, except sensitive fields, which keep their value unless given. The first participant has to stay the person it is recorded for. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateLifeEvent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEvents.update';

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
            'life_event_type' => $schema->string()->description('The id of another type, as list-life-event-types gives it. Choosing one moves the life event to the custom fields of that type and lets the old values go.'),
            'happened_on' => $schema->string()->description('The day it happened, as YYYY-MM-DD.'),
            'participants' => $schema->array()->items($schema->string())->description('The ids of the contacts who were there, in order, as list-contacts gives them. The list replaces the one recorded. The first has to stay the person it is recorded for.'),
            'title' => $schema->string()->nullable()->description('A title.'),
            'narrative' => $schema->string()->nullable()->description('What happened.'),
            'location' => $schema->string()->nullable()->description('Where it happened.'),
            'duration_in_minutes' => $schema->integer()->nullable()->description('How long it lasted, in minutes.'),
            'fields' => $schema->object()->nullable()->description('Custom field values, keyed by the field id get-life-event gives. A repeating group takes a list of repetitions, each as {id, values}: the id is the row get-life-event gives, and the values are keyed by the ids of the fields inside the group.'),
        ];
    }
}
