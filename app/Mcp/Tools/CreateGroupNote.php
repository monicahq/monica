<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateGroupNote as CreateGroupNoteAction;
use App\Exceptions\NoteIsEmpty;
use App\Helpers\FlagState;
use App\Http\Resources\GroupNoteResource;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Write a note about a group as a whole, such as where they usually meet or who has the spare key. It stays on the group and never reaches the page of a member. The change is written to the account journal.')]
class CreateGroupNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.notes.create';

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
            'body' => ['required', 'string', 'max:65535'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $group = $this->group($vault, $validated['group']);

        try {
            $note = new CreateGroupNoteAction(user: $this->user($request), vault: $vault, group: $group, body: $validated['body'])->execute();
        } catch (NoteIsEmpty $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new GroupNoteResource($note->load(GroupNoteResource::RELATIONS))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'body' => $schema->string()->description('What to write down, as plain text.')->required(),
        ];
    }
}
