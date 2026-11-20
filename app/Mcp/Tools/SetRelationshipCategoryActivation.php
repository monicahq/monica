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

#[Description('Offer a category of relationship types when contacts are linked, or stop offering it. Relationships already written stay. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class SetRelationshipCategoryActivation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipCategories.activation.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_category' => ['required', 'string'],
        ]);

        $relationshipCategory = $this->relationshipCategory($request, $validated['relationship_category']);

        return $this->answerThroughApi($request, $relationshipCategory->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_category' => $schema->string()->description('The id of the category, as list-relationship-categories gives it.')->required(),
            'is_active' => $schema->boolean()->description('True to offer it, false to stop offering it.')->required(),
        ];
    }
}
