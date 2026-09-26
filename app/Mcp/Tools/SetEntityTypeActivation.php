<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\ActivateEntityType;
use App\Actions\DeactivateEntityType;
use App\Http\Resources\EntityTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Switch a kind of record on, so new records of it can be added, or off. Existing records stay. The change is written to the account journal.')]
class SetEntityTypeActivation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes.activation.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $entityType = $this->entityType($request, $validated['entity_type']);

        if ((bool) $validated['is_active'] === $entityType->is_active) {
            return Response::json(new EntityTypeResource($entityType)->resolve());
        }

        if ($validated['is_active']) {
            new ActivateEntityType(user: $this->user($request), entityType: $entityType)->execute();
        }

        if (! $validated['is_active']) {
            new DeactivateEntityType(user: $this->user($request), entityType: $entityType)->execute();
        }

        return Response::json(new EntityTypeResource($entityType->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'entity_type' => $schema->string()->description('The identifier of the entity type, as list-entity-types gives it.')->required(),
            'is_active' => $schema->boolean()->description('True to switch it on, false to switch it off.')->required(),
        ];
    }
}
