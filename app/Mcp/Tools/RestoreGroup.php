<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\RestoreGroup as RestoreGroupAction;
use App\Helpers\FlagState;
use App\Http\Resources\GroupResource;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Bring a deleted group back, with every membership it held. The change is written to the account journal.')]

class RestoreGroup extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.restoration.update';

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
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $group = $this->group($vault, $validated['group'], withTrashed: true);

        if (! $group->trashed()) {
            return Response::error(__('That group is not deleted.'));
        }

        new RestoreGroupAction(
            user: $this->user($request),
            vault: $vault,
            group: $group,
        )->execute();

        return Response::json(new GroupResource($this->group($vault, $group->uuid))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the deleted group.')->required(),
        ];
    }
}
