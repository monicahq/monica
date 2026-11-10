<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\FlagState;
use App\Http\Resources\GroupNoteResource;
use App\Models\GroupNote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the notes written about a group, the newest first, a page at a time. A note belongs to the group alone and never appears on the page of a member.')]
#[IsReadOnly]
class ListGroupNotes extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.notes';

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

        $notes = GroupNote::query()
            ->where('group_id', $group->id)
            ->with(GroupNoteResource::RELATIONS)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return Response::json([
            'data' => array_map(fn (GroupNote $note): array => new GroupNoteResource($note)->resolve(), $notes->items()),
            'meta' => [
                'page' => $notes->currentPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
            ],
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'page' => $schema->integer()->description('Which page to return, starting at 1.'),
            'per_page' => $schema->integer()->description('How many notes per page.'),
        ];
    }
}
