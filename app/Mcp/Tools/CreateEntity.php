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

#[Description('Write down a new record of an entity type in a vault, such as an animal, on the template the vault uses for that type unless another template of the type is named. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateEntity extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entities.create';

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
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'entity_type' => $schema->string()->description('The id of the entity type, as list-entity-types gives it.')->required(),
            'name' => $schema->string()->description('What the record is called.')->required(),
            'structure_version_id' => $schema->integer()->description('The id of the published version of one of your active templates for this entity type, as list-template-versions gives it, which says what the record holds. Left out, the record starts from the template the vault uses for that type.'),
            'fields' => $schema->object()->description('Values keyed by the field id get-template-version gives for the template the record starts from. A repeating group takes a list of repetitions, each as {values}, keyed by the ids of the fields inside the group. A field left out starts with its default.'),
        ];
    }
}
