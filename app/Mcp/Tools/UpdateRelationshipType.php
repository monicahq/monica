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

#[Description('Rename a kind of relationship, change its roles or whether it reads the same both ways, or move it to another category. Whether it is offered changes with set-relationship-type-activation. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateRelationshipType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipTypes.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_type' => ['required', 'string'],
            'relationship_category_id' => ['nullable', 'string'],
        ]);

        if (isset($validated['relationship_category_id'])) {
            $this->relationshipCategory($request, $validated['relationship_category_id']);
        }

        $relationshipType = $this->relationshipType($request, $validated['relationship_type']);

        return $this->answerThroughApi($request, $relationshipType->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_type' => $schema->string()->description('The id of the relationship type, as list-relationship-types gives it.')->required(),
            'relationship_category_id' => $schema->string()->description('The id of the category to move it to, as list-relationship-categories gives it.'),
            'name' => $schema->string()->description('The new name.'),
            'role_a' => $schema->string()->description('The new role of the first contact.'),
            'role_b' => $schema->string()->nullable()->description('The new role of the other contact. It cannot be null when is_symmetric is false.'),
            'is_symmetric' => $schema->boolean()->nullable()->description('True when both contacts hold the same role. A type that came with the account, or that relationships use, keeps its direction.'),
        ];
    }
}
