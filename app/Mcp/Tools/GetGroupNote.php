<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\FlagState;
use App\Http\Resources\GroupNoteResource;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Return one note written about a group: what it says and when it was written.')]
#[IsReadOnly]
class GetGroupNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.notes.show';

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
            'note' => ['required', 'string'],
        ]);

        $group = $this->group($this->vault($request, $validated['vault']), $validated['group']);

        return Response::json(new GroupNoteResource($this->groupNote($group, $validated['note']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'note' => $schema->string()->description('The id of the note, as list-group-notes gives it.')->required(),
        ];
    }
}
