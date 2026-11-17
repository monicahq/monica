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

#[Description('Delete a kind of record. A type still holding records is refused; switch it off instead. The change is written to the account log.')]
#[IsDestructive]
class DeleteEntityType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
        ]);

        $entityType = $this->entityType($request, $validated['entity_type']);

        $answer = $this->answerThroughApi($request, $entityType->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The entity type is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'entity_type' => $schema->string()->description('The id of the entity type, as list-entity-types gives it.')->required(),
        ];
    }
}
