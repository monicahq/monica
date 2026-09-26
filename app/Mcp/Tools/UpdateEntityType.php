<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateEntityType as UpdateEntityTypeAction;
use App\Http\Resources\EntityTypeResource;
use App\Models\EntityType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a kind of record or change its description, icon or colour. Anything left out keeps its value, and the slug never changes. The change is written to the account journal.')]
class UpdateEntityType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
            'name_singular' => ['sometimes', 'required', 'string', 'max:255'],
            'name_plural' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'icon' => ['sometimes', 'required', 'string', Rule::in(EntityType::ICONS)],
            'colour' => ['sometimes', 'required', 'string', Rule::in(EntityType::COLOURS)],
        ]);

        $entityType = $this->entityType($request, $validated['entity_type']);

        new UpdateEntityTypeAction(
            user: $this->user($request),
            entityType: $entityType,
            nameSingular: $validated['name_singular'] ?? $entityType->name_singular,
            namePlural: $validated['name_plural'] ?? $entityType->name_plural,
            description: array_key_exists('description', $validated) ? $validated['description'] : $entityType->description,
            icon: $validated['icon'] ?? $entityType->icon,
            colour: $validated['colour'] ?? $entityType->colour,
        )->execute();

        return Response::json(new EntityTypeResource($entityType->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'entity_type' => $schema->string()->description('The identifier of the entity type, as list-entity-types gives it.')->required(),
            'name_singular' => $schema->string()->description('The new name of one record.'),
            'name_plural' => $schema->string()->description('The new name of several records.'),
            'description' => $schema->string()->description('The new description. Null clears it.'),
            'icon' => $schema->string()->description('One of: '.implode(', ', EntityType::ICONS).'.'),
            'colour' => $schema->string()->description('One of: '.implode(', ', EntityType::COLOURS).'.'),
        ];
    }
}
