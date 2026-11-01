<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateGroupMembership;
use App\Enums\DatePrecisionEnum;
use App\Exceptions\ContactIsAlreadyInTheGroup;
use App\Exceptions\MembershipEndsBeforeItStarts;
use App\Helpers\FlagState;
use App\Http\Resources\GroupMembershipResource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change the role or the dates of one membership. Giving a leave date is how somebody leaves a group; somebody who comes back gets a new membership through add-group-member. What is left out keeps its value. The change is written to the account journal.')]
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
            'role' => ['nullable', 'string', 'max:255'],
            'joined_on' => ['nullable', 'date_format:Y-m-d'],
            'joined_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
            'left_on' => ['nullable', 'date_format:Y-m-d'],
            'left_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $membership = $this->groupMembership($this->group($vault, $validated['group']), $validated['membership']);

        $joinedOn = array_key_exists('joined_on', $validated) ? $validated['joined_on'] : $membership->joined_on?->format('Y-m-d');
        $leftOn = array_key_exists('left_on', $validated) ? $validated['left_on'] : $membership->left_on?->format('Y-m-d');
        $joinedPrecision = $validated['joined_precision'] ?? $membership->joined_precision->value;
        $leftPrecision = $validated['left_precision'] ?? $membership->left_precision->value;

        try {
            new UpdateGroupMembership(
                user: $this->user($request),
                vault: $vault,
                membership: $membership,
                role: array_key_exists('role', $validated) ? $validated['role'] : $membership->role,
                joinedOn: $joinedOn !== null ? CarbonImmutable::parse($joinedOn) : null,
                joinedPrecision: $joinedPrecision === DatePrecisionEnum::Unknown->value ? DatePrecisionEnum::Day : DatePrecisionEnum::from($joinedPrecision),
                leftOn: $leftOn !== null ? CarbonImmutable::parse($leftOn) : null,
                leftPrecision: $leftPrecision === DatePrecisionEnum::Unknown->value ? DatePrecisionEnum::Day : DatePrecisionEnum::from($leftPrecision),
            )->execute();
        } catch (ContactIsAlreadyInTheGroup|MembershipEndsBeforeItStarts $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new GroupMembershipResource($membership->fresh(GroupMembershipResource::RELATIONS))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'membership' => $schema->string()->description('The id of the membership, as list-group-members gives it.')->required(),
            'role' => $schema->string()->description('What the contact is in the group, in their own words, such as Father or Temp.'),
            'joined_on' => $schema->string()->description('When they joined, as YYYY-MM-DD.'),
            'joined_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the join is known.'),
            'left_on' => $schema->string()->description('When they left, as YYYY-MM-DD. Giving one is how somebody leaves the group.'),
            'left_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the departure is known.'),
        ];
    }
}
