<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Add a partner, a family member or any other relative to a contact, with or without a record of their own, by linking them with a relationship type. The other person is an existing contact, a new person named here, or somebody whose name is unknown. One relationship holds both sides, so there is no reverse to add. A first name always creates a new person, even when somebody of that name exists. To link somebody who already exists, such as a child already added to the other parent, pass their id as other_contact: the answer of this tool gives it as contact_a or contact_b, and list-contacts finds people without a record of their own with the filter names-only. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateContactRelationship extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.relationships.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'other_contact' => ['nullable', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);

        if (($validated['other_contact'] ?? null) !== null) {
            $this->contact($vault, $validated['other_contact']);
        }

        return $this->answerThroughApi($request, $vault->uuid, $contact->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault holding both people, as list-vaults gives it.')->required(),
            'contact' => $schema->string()->description('The id of the contact the relationship is recorded from, as list-contacts gives it.')->required(),
            'relationship_type' => $schema->string()->description('The id of the relationship type, as list-relationship-types gives it. A type names a role for each side: role_a and role_b. A type that is symmetric gives both sides the same role, as partners do.')->required(),
            'other_contact' => $schema->string()->description('The id of the other person, an existing contact of the same vault, with or without a record of their own: list-contacts gives it, with the filter names-only for people without a record, and so does the answer of an earlier relationship. Leave other_contact out to name a new person with first_name, or to record somebody whose name is unknown.'),
            'first_name' => $schema->string()->description('The first name of a new person, who is created by this call. Not allowed with other_contact. Never use it for somebody created by an earlier call.'),
            'last_name' => $schema->string()->description('The last name of the other person. Needs first_name.'),
            'creates_record' => $schema->boolean()->description('True to give the person named here a full record of their own in the vault. False when left out: they then exist only through this relationship.'),
            'holds_role_a' => $schema->boolean()->description('Whether the contact holds role_a of the type and the other person role_b. For parent and child, where role_a is parent, true says the contact is the parent. True when left out. Ignored for a symmetric type.'),
        ];
    }
}
