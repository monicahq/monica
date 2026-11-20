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

#[Description('Read one kind of relationship: its name, the role of each contact, its category, whether it reads the same both ways, whether it is offered, whether it came with the account and how many relationships use it.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetRelationshipType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipTypes.show';

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
        ];
    }
}
