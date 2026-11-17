<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\RelationshipStatusEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change a relationship of a contact: its type, whether it is current or ended, its dates, which side holds which role, or the name of somebody who exists only through it. The change is written to the account log.')]
class UpdateContactRelationship extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.relationships.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'relationship' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);
        $relationship = $this->relationship($contact, $validated['relationship']);

        return $this->answerThroughApi($request, $vault->uuid, $contact->uuid, $relationship->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'contact' => $schema->string()->description('The id of either person in the relationship, as list-contacts gives it. holds_role_a is read from the side of this person.')->required(),
            'relationship' => $schema->string()->description('The id of the relationship, as list-contact-relationships gives it.')->required(),
            'relationship_type' => $schema->string()->description('The id of a new relationship type, as list-relationship-types gives it.'),
            'status' => $schema->string()->enum(RelationshipStatusEnum::values())->description('Whether the relationship is current or ended. Setting it back to current clears the end date.'),
            'started_on' => $schema->string()->nullable()->description('When it started, as YYYY-MM-DD.'),
            'ended_on' => $schema->string()->nullable()->description('When it ended, as YYYY-MM-DD, for an ended relationship. The end cannot fall before the start.'),
            'holds_role_a' => $schema->boolean()->description('Whether the contact holds role_a of the type and the other person role_b. For parent and child, where role_a is parent, true says the contact is the parent. Ignored for a symmetric type.'),
            'first_name' => $schema->string()->description('A first name for the other person, only when they exist through this relationship alone.'),
            'last_name' => $schema->string()->description('The last name of the other person. Needs first_name.'),
            'creates_record' => $schema->boolean()->description('True to give the other person, who exists through this relationship alone, a full record of their own.'),
        ];
    }
}
