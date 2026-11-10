<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\FlagState;
use App\Http\Resources\GroupFileResource;
use App\Models\GroupFile;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the files kept with a group, the newest first, a page at a time: name, type and size. The content of a file is not returned, and a file cannot be uploaded from here.')]
#[IsReadOnly]
class ListGroupFiles extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.files';

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User
            && FlagState::isOn('groups', $user->account)
            && FlagState::isOn('file-upload', $user->account);
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

        $attachments = GroupFile::query()
            ->where('group_id', $group->id)
            ->with(GroupFileResource::RELATIONS)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return Response::json([
            'data' => array_map(fn (GroupFile $attachment): array => new GroupFileResource($attachment)->resolve(), $attachments->items()),
            'meta' => [
                'page' => $attachments->currentPage(),
                'per_page' => $attachments->perPage(),
                'total' => $attachments->total(),
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
            'per_page' => $schema->integer()->description('How many files per page.'),
        ];
    }
}
