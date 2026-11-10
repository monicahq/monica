<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyGroupNote;
use App\Helpers\FlagState;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a note about a group. Nothing else changes. The change is written to the account journal.')]
#[IsDestructive]
class DeleteGroupNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.notes.destroy';

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

        $vault = $this->vault($request, $validated['vault']);
        $group = $this->group($vault, $validated['group']);

        new DestroyGroupNote(user: $this->user($request), vault: $vault, group: $group, note: $this->groupNote($group, $validated['note']))->execute();

        return Response::text(__('The note is deleted.'));
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
