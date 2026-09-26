<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateEntityType as CreateEntityTypeAction;
use App\Http\Resources\EntityTypeResource;
use App\Models\EntityType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a kind of record the account keeps besides contacts, such as Animal or Wine bottle. The change is written to the account journal.')]
class CreateEntityType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes.create';

    public function handle(Request $request): Response
    {
        $user = $this->user($request);

        $validated = $request->validate([
            'name_singular' => ['required', 'string', 'max:255'],
            'name_plural' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'icon' => ['nullable', 'string', Rule::in(EntityType::ICONS)],
            'colour' => ['nullable', 'string', Rule::in(EntityType::COLOURS)],
            'is_active' => ['nullable', 'boolean'],
            'slug' => [
                'nullable',
                'string',
                'max:'.EntityType::SLUG_LENGTH,
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('entity_types', 'slug')->where('account_id', $user->account_id),
            ],
        ]);

        $entityType = new CreateEntityTypeAction(
            user: $user,
            nameSingular: $validated['name_singular'],
            namePlural: $validated['name_plural'],
            description: $validated['description'] ?? null,
            icon: $validated['icon'] ?? null,
            colour: $validated['colour'] ?? null,
            isActive: (bool) ($validated['is_active'] ?? true),
            slug: $validated['slug'] ?? null,
        )->execute();

        return Response::json(new EntityTypeResource($entityType)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name_singular' => $schema->string()->description('The name of one record, such as Wine bottle.')->required(),
            'name_plural' => $schema->string()->description('The name of several records, such as Wine bottles.')->required(),
            'description' => $schema->string()->description('A sentence saying what these records are for.'),
            'icon' => $schema->string()->description('One of: '.implode(', ', EntityType::ICONS).'.'),
            'colour' => $schema->string()->description('One of: '.implode(', ', EntityType::COLOURS).'.'),
            'is_active' => $schema->boolean()->description('Whether new records can be added. True when left out.'),
            'slug' => $schema->string()->description('The part of the address naming these records, in lowercase letters, numbers and dashes. Derived from the singular name when left out, and fixed once created.'),
        ];
    }
}
