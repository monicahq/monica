<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyEntityType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a kind of record. A type still holding records is refused; switch it off instead. The change is written to the account journal.')]
#[IsDestructive]
class DeleteEntityType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
        ]);

        try {
            new DestroyEntityType(
                user: $this->user($request),
                entityType: $this->entityType($request, $validated['entity_type']),
            )->execute();
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage());
        }

        return Response::text(__('The entity type is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'entity_type' => $schema->string()->description('The identifier of the entity type, as list-entity-types gives it.')->required(),
        ];
    }
}
