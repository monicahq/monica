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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Stop keeping a file with a group. The file stays wherever else it is used, and is swept up after a grace period once nothing uses it. The change is written to the account log.')]
#[IsDestructive]
class RemoveGroupFile extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.files.destroy';

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
            'file' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $group = $this->group($vault, $validated['group']);
        $file = $this->groupFile($group, $validated['file']);

        $answer = $this->answerThroughApi($request, $vault->uuid, $group->uuid, $file->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The file is no longer kept with the group.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'file' => $schema->string()->description('The id of the file kept with the group, as list-group-files gives it.')->required(),
        ];
    }
}
