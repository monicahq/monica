<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateEntityTypePosition;
use App\Http\Resources\EntityTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a kind of record to another place in the list. The change is written to the account journal.')]
class SetEntityTypePosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes.position.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $entityType = $this->entityType($request, $validated['entity_type']);

        new UpdateEntityTypePosition(
            user: $this->user($request),
            entityType: $entityType,
            position: (int) $validated['position'],
        )->execute();

        return Response::json(new EntityTypeResource($entityType->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'entity_type' => $schema->string()->description('The identifier of the entity type, as list-entity-types gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes in the list, counting from 1.')->required(),
        ];
    }
}
