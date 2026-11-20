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

#[Description('List the records of one entity type in one vault, such as the animals, a page at a time, by name, each with its values.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListEntities extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entities';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'entity_type' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $entityType = $this->entityType($request, $validated['entity_type']);

        return $this->answerThroughApi($request, $vault->uuid, $entityType->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'entity_type' => $schema->string()->description('The id of the entity type, as list-entity-types gives it.')->required(),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many records a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
