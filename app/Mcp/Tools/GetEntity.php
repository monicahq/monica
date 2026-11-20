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

#[Description('Look one record of an entity type up in a vault, such as an animal, with every value its template holds.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetEntity extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entities.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'entity' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $entity = $this->entity($vault, $validated['entity']);

        return $this->answerThroughApi($request, $vault->uuid, $entity->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'entity' => $schema->string()->description('The id of the record, as list-entities gives it.')->required(),
        ];
    }
}
