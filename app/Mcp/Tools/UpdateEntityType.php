<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\EntityType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Rename a kind of record or change its description, icon or colour. The slug never changes. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateEntityType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes.update';

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
            'name_singular' => $schema->string()->description('The new name of one record.'),
            'name_plural' => $schema->string()->description('The new name of several records.'),
            'description' => $schema->string()->nullable()->description('The new description.'),
            'icon' => $schema->string()->enum(EntityType::ICONS)->description('The icon shown next to these records.'),
            'colour' => $schema->string()->enum(EntityType::COLOURS)->description('The colour of these records.'),
        ];
    }
}
