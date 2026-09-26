<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateRelationshipType as UpdateRelationshipTypeAction;
use App\Exceptions\CannotChangeARelationshipTypeDirection;
use App\Exceptions\RelationshipTypeNameIsAlreadyUsed;
use App\Http\Resources\RelationshipTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a kind of relationship, change its roles or whether it reads the same both ways, or move it to another category. Anything left out keeps its value; whether it is offered changes with set-relationship-type-activation. The change is written to the account journal.')]
class UpdateRelationshipType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipTypes.update';

    public function handle(Request $request): Response
    {
        $address = $request->validate([
            'relationship_type' => ['required', 'string'],
        ]);

        $type = $this->relationshipType($request, $address['relationship_type']);
        $wantsSymmetric = $request->boolean('is_symmetric', $type->is_symmetric);

        $validated = $request->validate([
            'relationship_category_id' => ['sometimes', 'required', 'string'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'role_a' => ['sometimes', 'required', 'string', 'max:255'],
            'role_b' => [
                Rule::requiredIf(fn (): bool => ! $wantsSymmetric && $type->is_symmetric),
                'nullable',
                'string',
                'max:255',
            ],
            'is_symmetric' => ['sometimes', 'boolean'],
        ]);

        $category = isset($validated['relationship_category_id'])
            ? $this->relationshipCategory($request, $validated['relationship_category_id'])
            : $type->relationshipCategory;
        $roleA = $validated['role_a'] ?? $type->role_a;

        try {
            new UpdateRelationshipTypeAction(
                user: $this->user($request),
                relationshipType: $type,
                relationshipCategory: $category,
                name: $validated['name'] ?? $type->name,
                roleA: $roleA,
                roleB: $validated['role_b'] ?? ($wantsSymmetric ? $roleA : $type->role_b),
                isSymmetric: $wantsSymmetric,
                isActive: $type->is_active,
            )->execute();
        } catch (RelationshipTypeNameIsAlreadyUsed) {
            return Response::error(__('Another type in this account already goes by that name.'));
        } catch (CannotChangeARelationshipTypeDirection) {
            return Response::error(__('This type keeps the direction it was written with.'));
        }

        return Response::json(new RelationshipTypeResource($type->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_type' => $schema->string()->description('The identifier of the relationship type, as list-relationship-types gives it.')->required(),
            'relationship_category_id' => $schema->string()->description('The identifier of the category to move it to, as list-relationship-categories gives it.'),
            'name' => $schema->string()->description('The new name.'),
            'role_a' => $schema->string()->description('The new role of the first contact.'),
            'role_b' => $schema->string()->description('The new role of the other contact. Required when a type that read the same both ways stops doing so.'),
            'is_symmetric' => $schema->boolean()->description('True when both contacts hold the same role. A type that came with the account, or that relationships use, keeps its direction.'),
        ];
    }
}
