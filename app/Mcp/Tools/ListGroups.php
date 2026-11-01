<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\FlagState;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the groups of one vault, such as households and teams, by name, a page at a time, with how many people are in each today.')]
#[IsReadOnly]
class ListGroups extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups';

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && FlagState::isOn('groups', $user->account);
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        $perPage = max(1, min(
            (int) ($validated['per_page'] ?? config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $groups = Group::query()
            ->where('vault_id', $vault->id)
            ->with('vault')
            ->withCount('currentMembers')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return Response::json([
            'data' => array_map(fn (Group $group): array => new GroupResource($group)->resolve(), $groups->items()),
            'meta' => [
                'page' => $groups->currentPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault, as list-vaults gives it.')->required(),
            'page' => $schema->integer()->description('Which page to return, counting from 1.'),
            'per_page' => $schema->integer()->description('How many groups a page holds.'),
        ];
    }
}
