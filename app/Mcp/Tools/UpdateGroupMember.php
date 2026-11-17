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

#[Description('Change the role or the dates of one membership. Giving a leave date is how somebody leaves a group. Somebody who comes back gets a new membership through add-group-member. The change is written to the account log.')]
class UpdateGroupMember extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.members.update';

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
        $group = $this->group($vault, $validated['group']);
        $membership = $this->groupMembership($group, $validated['membership']);

        return $this->answerThroughApi($request, $vault->uuid, $group->uuid, $membership->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'membership' => $schema->string()->description('The id of the membership, as list-group-members gives it.')->required(),
            'role' => $schema->string()->nullable()->description('What the contact is in the group, in their own words, such as Father or Temp.'),
            'joined_on' => $schema->string()->nullable()->description('When they joined, as YYYY-MM-DD.'),
            'joined_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the join is known.'),
            'left_on' => $schema->string()->nullable()->description('When they left, as YYYY-MM-DD. Giving one is how somebody leaves the group.'),
            'left_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the departure is known.'),
        ];
    }
}
