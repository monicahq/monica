<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateRelationshipTypePosition;
use App\Http\Resources\RelationshipTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a kind of relationship to another place within its category. The change is written to the account journal.')]
class SetRelationshipTypePosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipTypes.position.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_type' => ['required', 'string'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $type = $this->relationshipType($request, $validated['relationship_type']);

        new UpdateRelationshipTypePosition(
            user: $this->user($request),
            relationshipType: $type,
            position: (int) $validated['position'],
        )->execute();

        return Response::json(new RelationshipTypeResource($type->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_type' => $schema->string()->description('The identifier of the relationship type, as list-relationship-types gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes within its category, counting from 1.')->required(),
        ];
    }
}
