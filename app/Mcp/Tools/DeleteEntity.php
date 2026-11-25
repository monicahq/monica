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

#[Description('Delete a record of an entity type, such as an animal. It goes to the trash of the vault, where restore-trash-item can bring it back for 30 days. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class DeleteEntity extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entities.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'entity' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $entity = $this->entity($vault, $validated['entity']);

        $answer = $this->answerThroughApi($request, $vault->uuid, $entity->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The record is in the trash of the vault.'));
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
