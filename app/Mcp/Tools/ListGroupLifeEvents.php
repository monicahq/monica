<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\FlagState;
use App\Http\Resources\LifeEventResource;
use App\Models\LifeEvent;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the activities recorded on a group, the most recent first, a page at a time. Each is also on the timeline of the people who were there. Values of sensitive fields are never given. Change or delete one with update-life-event and delete-life-event.')]
#[IsReadOnly]
class ListGroupLifeEvents extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.lifeEvents';

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
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $group = $this->group($this->vault($request, $validated['vault']), $validated['group']);

        $perPage = max(1, min(
            (int) ($validated['per_page'] ?? config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $lifeEvents = LifeEvent::query()
            ->where('group_id', $group->id)
            ->with(LifeEventResource::RELATIONS)
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        $withSensitive = $this->reachesSensitiveFields($request);

        return Response::json([
            'data' => array_map(function (LifeEvent $lifeEvent) use ($withSensitive): array {
                $resource = new LifeEventResource($lifeEvent);
                $resource->withSensitive = $withSensitive;

                return $resource->resolve();
            }, $lifeEvents->items()),
            'meta' => [
                'page' => $lifeEvents->currentPage(),
                'per_page' => $lifeEvents->perPage(),
                'total' => $lifeEvents->total(),
            ],
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'page' => $schema->integer()->description('Which page to return, counting from 1.'),
            'per_page' => $schema->integer()->description("How many activities a page holds, {$default} by default and {$maximum} at most."),
        ];
    }
}
