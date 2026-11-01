<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyGroupMembership;
use App\Helpers\FlagState;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a membership that was recorded by mistake. Somebody who really left keeps their membership with a leave date instead, given through update-group-member. The change is written to the account journal.')]
#[IsDestructive]
class RemoveGroupMember extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.members.destroy';

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
            'membership' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        new DestroyGroupMembership(
            user: $this->user($request),
            vault: $vault,
            membership: $this->groupMembership($this->group($vault, $validated['group']), $validated['membership']),
        )->execute();

        return Response::text(__('The membership is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'membership' => $schema->string()->description('The id of the membership, as list-group-members gives it.')->required(),
        ];
    }
}
