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

#[Description('Move a kind of record to another place in the list. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class SetEntityTypePosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes.position.update';

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
            'position' => $schema->integer()->description('Where the entity type goes in the list, counting from 1.')->required(),
        ];
    }
}
