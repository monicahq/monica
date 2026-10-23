<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateRelationship;
use App\Exceptions\CannotLinkSomebodyToThemselves;
use App\Exceptions\RelationshipTypeIsNotAvailable;
use App\Exceptions\RelationshipWouldCrossVaults;
use App\Exceptions\RelationshipWouldLoopParentage;
use App\Exceptions\VaultHasNoContactDefault;
use App\Http\Resources\RelationshipResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Link a contact to somebody else in the same vault with a relationship type, such as partner or parent. The other person is an existing contact, a new person named here, or somebody whose name is unknown. One relationship holds both sides, so there is no reverse to add. The change is written to the account journal.')]
class CreateContactRelationship extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.relationships.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'relationship_type' => ['required', 'string'],
            'other_contact' => ['nullable', 'string', 'prohibits:first_name,last_name'],
            'first_name' => ['nullable', 'required_with:last_name', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'creates_record' => ['nullable', 'boolean'],
            'holds_role_a' => ['nullable', 'boolean'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);
        $relationshipType = $this->relationshipType($request, $validated['relationship_type']);

        $otherContact = ($validated['other_contact'] ?? null) === null
            ? null
            : $this->contact($vault, $validated['other_contact']);

        try {
            $relationship = new CreateRelationship(
                user: $this->user($request),
                vault: $vault,
                contact: $contact,
                relationshipType: $relationshipType,
                otherContact: $otherContact,
                firstName: $validated['first_name'] ?? null,
                lastName: $validated['last_name'] ?? null,
                createsRecord: $request->boolean('creates_record'),
                swapped: ! $request->boolean('holds_role_a', true),
            )->execute();
        } catch (CannotLinkSomebodyToThemselves|RelationshipWouldCrossVaults|RelationshipTypeIsNotAvailable|RelationshipWouldLoopParentage|AuthorizationException|VaultHasNoContactDefault $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new RelationshipResource($this->relationship($contact, $relationship->uuid))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding both people.')->required(),
            'contact' => $schema->string()->description('The address of the contact the relationship is recorded from.')->required(),
            'relationship_type' => $schema->string()->description('The identifier of the relationship type, as list-relationship-types gives it. A type names a role for each side: role_a and role_b. A type that is symmetric gives both sides the same role, as partners do.')->required(),
            'other_contact' => $schema->string()->description('The address of the other person, an existing contact of the same vault. Leave it out to name a new person with first_name, or to record somebody whose name is unknown.'),
            'first_name' => $schema->string()->description('The first name of the other person when they are not a contact yet. Not allowed with other_contact.'),
            'last_name' => $schema->string()->description('Their last name. Needs first_name.'),
            'creates_record' => $schema->boolean()->description('True to give the person named here a full record of their own in the vault. False when left out: they then exist only through this relationship.'),
            'holds_role_a' => $schema->boolean()->description('Whether the contact holds role_a of the type and the other person role_b. For parent and child, where role_a is parent, true says the contact is the parent. True when left out. Ignored for a symmetric type.'),
        ];
    }
}
