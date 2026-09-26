<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\ActivateRelationshipType;
use App\Actions\DeactivateRelationshipType;
use App\Http\Resources\RelationshipTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Offer a kind of relationship when contacts are linked, or stop offering it. Relationships already written with it stay. The change is written to the account journal.')]
class SetRelationshipTypeActivation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipTypes.activation.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_type' => ['required', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $type = $this->relationshipType($request, $validated['relationship_type']);

        if ((bool) $validated['is_active'] === $type->is_active) {
            return Response::json(new RelationshipTypeResource($type)->resolve());
        }

        if ($validated['is_active']) {
            new ActivateRelationshipType(user: $this->user($request), relationshipType: $type)->execute();
        }

        if (! $validated['is_active']) {
            new DeactivateRelationshipType(user: $this->user($request), relationshipType: $type)->execute();
        }

        return Response::json(new RelationshipTypeResource($type->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_type' => $schema->string()->description('The identifier of the relationship type, as list-relationship-types gives it.')->required(),
            'is_active' => $schema->boolean()->description('True to offer it, false to stop offering it.')->required(),
        ];
    }
}
