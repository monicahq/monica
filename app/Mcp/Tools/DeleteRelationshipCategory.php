<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyRelationshipCategory;
use App\Exceptions\CannotChangeASuppliedRelationshipCategory;
use App\Exceptions\CannotDeleteARelationshipCategoryInUse;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a category the account created. One that came with the account, or that still holds types, is refused; move its types to another category first. The change is written to the account journal.')]
#[IsDestructive]
class DeleteRelationshipCategory extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipCategories.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_category' => ['required', 'string'],
        ]);

        try {
            new DestroyRelationshipCategory(
                user: $this->user($request),
                relationshipCategory: $this->relationshipCategory($request, $validated['relationship_category']),
            )->execute();
        } catch (CannotChangeASuppliedRelationshipCategory) {
            return Response::error(__('The category came with your account and stays.'));
        } catch (CannotDeleteARelationshipCategoryInUse) {
            return Response::error(__('The category still holds types and stays.'));
        }

        return Response::text(__('The relationship category is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_category' => $schema->string()->description('The identifier of the category, as list-relationship-categories gives it.')->required(),
        ];
    }
}
