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

#[Description('Write down something that happened, on a date, with the people who were there. It goes on the timeline of every one of them, the first being the person it is recorded for. Read the custom fields of the type with get-life-event-type-fields first: values are keyed by field id. A file field cannot be written. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateLifeEvent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEvents.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        return $this->answerThroughApi($request, $vault->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'life_event_type' => $schema->string()->description('The id of its type, as list-life-event-types gives it. A type switched off cannot be chosen.')->required(),
            'happened_on' => $schema->string()->description('The day it happened, as YYYY-MM-DD.')->required(),
            'participants' => $schema->array()->items($schema->string())->description('The ids of the contacts who were there, in order, as list-contacts gives them. The first is the person it is recorded for.')->required(),
            'title' => $schema->string()->description('A title. Without one it is called by the name of its type.'),
            'narrative' => $schema->string()->description('What happened.'),
            'location' => $schema->string()->description('Where it happened.'),
            'duration_in_minutes' => $schema->integer()->description('How long it lasted, in minutes.'),
            'fields' => $schema->object()->description('The values of the custom fields, keyed by the field id get-life-event-type-fields gives. A select field takes the id of its choice.'),
        ];
    }
}
