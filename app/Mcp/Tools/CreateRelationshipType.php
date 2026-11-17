<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a kind of relationship two contacts can be linked by, such as godparent and godchild, in one of the account’s categories. The change is written to the account log.')]
class CreateRelationshipType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipTypes.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_category_id' => ['required', 'string'],
        ]);

        $this->relationshipCategory($request, $validated['relationship_category_id']);

        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_category_id' => $schema->string()->description('The id of the category, as list-relationship-categories gives it.')->required(),
            'name' => $schema->string()->description('The name of the kind of relationship, such as Godparent.')->required(),
            'role_a' => $schema->string()->description('The role of the first contact, such as godparent.')->required(),
            'role_b' => $schema->string()->nullable()->description('The role of the other contact, such as godchild. Required when is_symmetric is false.'),
            'is_symmetric' => $schema->boolean()->nullable()->description('True when both contacts hold the same role, as with siblings. True when left out.'),
            'is_active' => $schema->boolean()->nullable()->description('Whether it is offered when contacts are linked. True when left out.'),
        ];
    }
}
