<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateGroupMembership;
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

#[Description('Put a contact of the vault in a group, optionally with a role and the dates they joined and left. Somebody already in the group today cannot be added twice. The change is written to the account journal.')]
class AddGroupMember extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.members.create';

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
            'contact' => ['required', 'string'],
            'role' => ['nullable', 'string', 'max:255'],
            'joined_on' => ['nullable', 'date_format:Y-m-d'],
            'joined_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
            'left_on' => ['nullable', 'date_format:Y-m-d'],
            'left_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $group = $this->group($vault, $validated['group']);
        $contact = $this->contact($vault, $validated['contact']);

        try {
            $membership = new CreateGroupMembership(
                user: $this->user($request),
                vault: $vault,
                group: $group,
                contact: $contact,
                role: $validated['role'] ?? null,
                joinedOn: isset($validated['joined_on']) ? CarbonImmutable::parse($validated['joined_on']) : null,
                joinedPrecision: DatePrecisionEnum::from($validated['joined_precision'] ?? DatePrecisionEnum::Day->value),
                leftOn: isset($validated['left_on']) ? CarbonImmutable::parse($validated['left_on']) : null,
                leftPrecision: DatePrecisionEnum::from($validated['left_precision'] ?? DatePrecisionEnum::Day->value),
            )->execute();
        } catch (ContactIsAlreadyInTheGroup|MembershipEndsBeforeItStarts $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new GroupMembershipResource($membership->load(GroupMembershipResource::RELATIONS))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'contact' => $schema->string()->description('The address of the contact to add, as list-contacts gives it.')->required(),
            'role' => $schema->string()->description('What the contact is in the group, in their own words, such as Father or Temp.'),
            'joined_on' => $schema->string()->description('When they joined, as YYYY-MM-DD.'),
            'joined_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the join is known.'),
            'left_on' => $schema->string()->description('When they left, as YYYY-MM-DD. Giving one is how somebody leaves the group.'),
            'left_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the departure is known.'),
        ];
    }
}
