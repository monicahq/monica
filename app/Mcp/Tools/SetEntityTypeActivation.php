<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Switch a kind of record on, so new records of it can be added, or off. Existing records stay. The change is written to the account log.')]
class SetEntityTypeActivation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes.activation.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
        ]);

        $entityType = $this->entityType($request, $validated['entity_type']);

        return $this->answerThroughApi($request, $entityType->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'entity_type' => $schema->string()->description('The id of the entity type, as list-entity-types gives it.')->required(),
            'is_active' => $schema->boolean()->description('True to switch the entity type on, false to switch it off.')->required(),
        ];
    }
}
