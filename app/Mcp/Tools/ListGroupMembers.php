<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\FlagState;
use App\Http\Resources\GroupMembershipResource;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the memberships of a group, the people in it today first and then those who left, each with their role and dates, a page at a time.')]
#[IsReadOnly]
class ListGroupMembers extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.members';

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

        $memberships = GroupMember::query()
            ->where('group_id', $group->id)
            ->whereHas('contact')
            ->with(GroupMembershipResource::RELATIONS)
            ->orderByRaw('case when left_on is null then 0 else 1 end')
            ->orderByDesc('left_on')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return Response::json([
            'data' => array_map(fn (GroupMember $membership): array => new GroupMembershipResource($membership)->resolve(), $memberships->items()),
            'meta' => [
                'page' => $memberships->currentPage(),
                'per_page' => $memberships->perPage(),
                'total' => $memberships->total(),
            ],
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'page' => $schema->integer()->description('Which page to return, counting from 1.'),
            'per_page' => $schema->integer()->description('How many memberships a page holds.'),
        ];
    }
}
