<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a kind of relationship to another place within its category. The change is written to the account log.')]
class SetRelationshipTypePosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipTypes.position.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_type' => ['required', 'string'],
        ]);

        $relationshipType = $this->relationshipType($request, $validated['relationship_type']);

        return $this->answerThroughApi($request, $relationshipType->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_type' => $schema->string()->description('The id of the relationship type, as list-relationship-types gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes within its category, counting from 1.')->required(),
        ];
    }
}
