<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\FlagState;
use App\Http\Resources\GroupMembershipResource;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the groups one contact belongs to or belonged to, the current ones first, with their role and dates in each.')]
#[IsReadOnly]
class ListContactGroups extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.groups';

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && FlagState::isOn('groups', $user->account);
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);

        $memberships = GroupMember::query()
            ->where('contact_id', $contact->id)
            ->whereHas('group', fn (Builder $query): Builder => $query->where('vault_id', $vault->id))
            ->with(GroupMembershipResource::RELATIONS)
            ->orderByRaw('case when left_on is null then 0 else 1 end')
            ->orderByDesc('left_on')
            ->orderBy('id')
            ->get();

        return Response::json([
            'data' => $memberships->map(fn (GroupMember $membership): array => new GroupMembershipResource($membership)->resolve())->all(),
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of the contact, as list-contacts gives it.')->required(),
        ];
    }
}
