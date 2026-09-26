<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyRelationshipType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a kind of relationship the account created. One that came with the account, or that relationships use, is refused; stop offering it instead. The change is written to the account journal.')]
#[IsDestructive]
class DeleteRelationshipType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipTypes.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_type' => ['required', 'string'],
        ]);

        try {
            new DestroyRelationshipType(
                user: $this->user($request),
                relationshipType: $this->relationshipType($request, $validated['relationship_type']),
            )->execute();
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage());
        }

        return Response::text(__('The relationship type is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_type' => $schema->string()->description('The identifier of the relationship type, as list-relationship-types gives it.')->required(),
        ];
    }
}
