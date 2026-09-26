<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\EntityTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one kind of record the account keeps besides contacts: its names, slug, icon, colour, position, whether new records can be added and how many exist.')]
#[IsReadOnly]
class GetEntityType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
        ]);

        return Response::json(new EntityTypeResource($this->entityType($request, $validated['entity_type']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'entity_type' => $schema->string()->description('The identifier of the entity type, as list-entity-types gives it.')->required(),
        ];
    }
}
