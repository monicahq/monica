<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\RelationshipResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one relationship of a contact: the type, both people with the role each holds, whether it is current or ended, and its dates.')]
#[IsReadOnly]
class GetContactRelationship extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.relationships.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'relationship' => ['required', 'string'],
        ]);

        $contact = $this->contact($this->vault($request, $validated['vault']), $validated['contact']);

        return Response::json(new RelationshipResource($this->relationship($contact, $validated['relationship']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of either person in the relationship.')->required(),
            'relationship' => $schema->string()->description('The identifier of the relationship, as list-contact-relationships gives it.')->required(),
        ];
    }
}
