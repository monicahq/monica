<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateRelationship;
use App\Enums\RelationshipStatusEnum;
use App\Exceptions\RelationshipDatesAreOutOfOrder;
use App\Exceptions\RelationshipTypeIsNotAvailable;
use App\Exceptions\RelationshipWouldLoopParentage;
use App\Http\Resources\RelationshipResource;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change a relationship of a contact: its type, whether it is current or ended, its dates, which side holds which role, or the name of somebody who exists only through it. What you leave out keeps its value. The change is written to the account journal.')]
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
            'relationship_type' => ['sometimes', 'required', 'string'],
            'status' => ['sometimes', 'required', Rule::in(RelationshipStatusEnum::values())],
            'started_on' => ['nullable', 'date_format:Y-m-d'],
            'ended_on' => ['nullable', 'date_format:Y-m-d'],
            'holds_role_a' => ['sometimes', 'required', 'boolean'],
            'first_name' => ['nullable', 'required_with:last_name', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'creates_record' => ['nullable', 'boolean'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);
        $relationship = $this->relationship($contact, $validated['relationship']);

        $names = ($validated['first_name'] ?? null) !== null || $request->boolean('creates_record');
        $other = $relationship->contact_a_id === $contact->id ? $relationship->contactB : $relationship->contactA;

        if ($names && ! $other->is_partial) {
            return Response::error(__('Only somebody without a record of their own can be named here.'));
        }

        $relationshipType = array_key_exists('relationship_type', $validated)
            ? $this->relationshipType($request, $validated['relationship_type'])
            : $relationship->relationshipType;

        try {
            new UpdateRelationship(
                user: $this->user($request),
                vault: $vault,
                contact: $contact,
                relationship: $relationship,
                relationshipType: $relationshipType,
                status: array_key_exists('status', $validated) ? RelationshipStatusEnum::from($validated['status']) : $relationship->status,
                startedOn: $this->date($validated, 'started_on', $relationship->started_on),
                endedOn: $this->date($validated, 'ended_on', $relationship->ended_on),
                contactHoldsRoleA: $request->boolean('holds_role_a', $relationship->contact_a_id === $contact->id),
                otherFirstName: $validated['first_name'] ?? null,
                otherLastName: $validated['last_name'] ?? null,
                createsRecord: $request->boolean('creates_record'),
            )->execute();
        } catch (RelationshipTypeIsNotAvailable|RelationshipWouldLoopParentage|RelationshipDatesAreOutOfOrder $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new RelationshipResource($this->relationship($contact, $relationship->uuid))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of either person in the relationship. holds_role_a is read from this person\'s side.')->required(),
            'relationship' => $schema->string()->description('The identifier of the relationship, as list-contact-relationships gives it.')->required(),
            'relationship_type' => $schema->string()->description('The identifier of a new relationship type, as list-relationship-types gives it.'),
            'status' => $schema->string()->description('current or ended. Setting it back to current clears the end date.'),
            'started_on' => $schema->string()->description('When it started, as YYYY-MM-DD. Null clears it.'),
            'ended_on' => $schema->string()->description('When it ended, as YYYY-MM-DD, for an ended relationship. It cannot fall before the start. Null clears it.'),
            'holds_role_a' => $schema->boolean()->description('Whether the contact holds role_a of the type and the other person role_b. For parent and child, where role_a is parent, true says the contact is the parent. Ignored for a symmetric type.'),
            'first_name' => $schema->string()->description('A first name for the other person, only when they exist through this relationship alone.'),
            'last_name' => $schema->string()->description('Their last name. Needs first_name.'),
            'creates_record' => $schema->boolean()->description('True to give the other person, who exists through this relationship alone, a full record of their own.'),
        ];
    }

    /** @param  array<string, mixed>  $validated */
    private function date(array $validated, string $key, ?CarbonImmutable $current): ?CarbonImmutable
    {
        if (! array_key_exists($key, $validated)) {
            return $current;
        }

        return $validated[$key] === null ? null : CarbonImmutable::parse($validated[$key]);
    }
}
