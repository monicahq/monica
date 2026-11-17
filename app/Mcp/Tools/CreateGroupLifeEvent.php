<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\FlagState;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record a life event on a group, such as a meeting or an outing, on a date, with the members who were there. It shows on the group page and on the timeline of every one of them, the first being the person it is recorded for. Read the custom fields of the type with get-life-event-type-fields first: values are keyed by field id. A file field cannot be written. The change is written to the account log.')]
class CreateGroupLifeEvent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.lifeEvents.create';

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && FlagState::isOn('groups', $user->account);
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'group' => ['required', 'string'],
            'life_event_type' => ['required', 'string'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*' => ['string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $group = $this->group($vault, $validated['group']);
        $this->lifeEventType($request, $validated['life_event_type']);

        foreach ($validated['participants'] as $participant) {
            $this->contact($vault, $participant);
        }

        return $this->answerThroughApi($request, $vault->uuid, $group->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'life_event_type' => $schema->string()->description('The id of its type, as list-life-event-types gives it. A type switched off cannot be chosen.')->required(),
            'happened_on' => $schema->string()->description('The day it happened, as YYYY-MM-DD.')->required(),
            'participants' => $schema->array()->items($schema->string())->description('The ids of the contacts who were there, in order, as list-contacts gives them. The first is the person it is recorded for.')->required(),
            'title' => $schema->string()->nullable()->description('A title. Without one it is called by the name of its type.'),
            'narrative' => $schema->string()->nullable()->description('What happened.'),
            'location' => $schema->string()->nullable()->description('Where it happened.'),
            'duration_in_minutes' => $schema->integer()->nullable()->description('How long it lasted, in minutes.'),
            'fields' => $schema->object()->description('The values of the custom fields, keyed by the field id get-life-event-type-fields gives. A select field takes the id of its choice.'),
        ];
    }
}
