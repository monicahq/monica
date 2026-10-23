<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\RelationshipResource;
use App\Models\Relationship;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the relationships of one contact, from either side: the type, both people with the role each holds, whether it is current or ended, and its dates.')]
#[IsReadOnly]
class ListContactRelationships extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.relationships';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
        ]);

        $contact = $this->contact($this->vault($request, $validated['vault']), $validated['contact']);

        $relationships = Relationship::query()
            ->forContact($contact)
            ->with(['vault', 'relationshipType', 'contactA', 'contactB'])
            ->orderBy('id')
            ->get();

        return Response::json(RelationshipResource::collection($relationships)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of the contact.')->required(),
        ];
    }
}
