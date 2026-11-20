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

#[Description('Correct a record of an entity type, such as an animal: its name and the values of its template. The record stays on the template version it uses. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateEntity extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entities.update';

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
            'name' => $schema->string()->description('What the record is called.'),
            'fields' => $schema->object()->description('Values keyed by the field id get-entity gives. A repeating group takes a list of repetitions, each as {id, values}: the id is the row get-entity gives, and the values are keyed by the ids of the fields inside the group.'),
        ];
    }
}
