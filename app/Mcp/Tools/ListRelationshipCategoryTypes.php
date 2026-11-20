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

#[Description('List the kinds of relationship filed under one category, in the order they are offered.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListRelationshipCategoryTypes extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipCategories.types';

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
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'relationship_category' => $schema->string()->description('The id of the category, as list-relationship-categories gives it.')->required(),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many relationship types a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
