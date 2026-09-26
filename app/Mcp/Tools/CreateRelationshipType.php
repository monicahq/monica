<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateRelationshipType as CreateRelationshipTypeAction;
use App\Exceptions\RelationshipTypeNameIsAlreadyUsed;
use App\Http\Resources\RelationshipTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a kind of relationship two contacts can be linked by, such as godparent and godchild, in one of the account’s categories. The change is written to the account journal.')]
class CreateRelationshipType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipTypes.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_category_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'role_a' => ['required', 'string', 'max:255'],
            'role_b' => [
                Rule::requiredIf(fn (): bool => ! $request->boolean('is_symmetric', true)),
                'nullable',
                'string',
                'max:255',
            ],
            'is_symmetric' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $type = new CreateRelationshipTypeAction(
                user: $this->user($request),
                relationshipCategory: $this->relationshipCategory($request, $validated['relationship_category_id']),
                name: $validated['name'],
                roleA: $validated['role_a'],
                roleB: $validated['role_b'] ?? $validated['role_a'],
                isSymmetric: $request->boolean('is_symmetric', true),
                isActive: $request->boolean('is_active', true),
            )->execute();
        } catch (RelationshipTypeNameIsAlreadyUsed) {
            return Response::error(__('Another type in this account already goes by that name.'));
        }

        return Response::json(new RelationshipTypeResource($type->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_category_id' => $schema->string()->description('The identifier of the category, as list-relationship-categories gives it.')->required(),
            'name' => $schema->string()->description('The name of the kind of relationship, such as Godparent.')->required(),
            'role_a' => $schema->string()->description('The role of the first contact, such as godparent.')->required(),
            'role_b' => $schema->string()->description('The role of the other contact, such as godchild. Required when is_symmetric is false.'),
            'is_symmetric' => $schema->boolean()->description('True when both contacts hold the same role, as with siblings. True when left out.'),
            'is_active' => $schema->boolean()->description('Whether it is offered when contacts are linked. True when left out.'),
        ];
    }
}
