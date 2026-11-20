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

#[Description('Delete a category the account created. One that came with the account, or that still holds types, is refused; move its types to another category first. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class DeleteRelationshipCategory extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipCategories.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_category' => ['required', 'string'],
        ]);

        $relationshipCategory = $this->relationshipCategory($request, $validated['relationship_category']);

        $answer = $this->answerThroughApi($request, $relationshipCategory->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The relationship category is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_category' => $schema->string()->description('The id of the category, as list-relationship-categories gives it.')->required(),
        ];
    }
}
