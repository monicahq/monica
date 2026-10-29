<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateTemplate as CreateTemplateAction;
use App\Enums\StructureAppliesToEnum;
use App\Http\Resources\TemplateResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a template for contacts, for a kind of entity or for life events. It starts with a first draft. The change is written to the account journal.')]
class CreateTemplate extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.create';

    public function handle(Request $request): Response
    {
        $user = $this->user($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'applies_to' => ['required', Rule::enum(StructureAppliesToEnum::class)],
            'description' => ['nullable', 'string', 'max:65535'],
            'entity_type' => [
                Rule::requiredIf(fn (): bool => $request->get('applies_to') === StructureAppliesToEnum::Entity->value),
                'nullable',
                'string',
                'bail',
                'uuid',
                Rule::exists('entity_types', 'uuid')->where('account_id', $user->account_id),
            ],
        ]);

        $template = new CreateTemplateAction(
            user: $user,
            name: $validated['name'],
            appliesTo: StructureAppliesToEnum::from($validated['applies_to']),
            entityType: isset($validated['entity_type'])
                ? $user->account->entityTypes()->where('uuid', $validated['entity_type'])->firstOrFail()
                : null,
            description: $validated['description'] ?? null,
        )->execute();

        return Response::json(new TemplateResource($template->load(['structure.entityType', 'publishedVersion']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The name of the template.')->required(),
            'applies_to' => $schema->string()->description('What it describes: '.implode(', ', array_column(StructureAppliesToEnum::cases(), 'value')).'.')->required(),
            'description' => $schema->string()->description('A sentence saying what the template is for.'),
            'entity_type' => $schema->string()->description('The kind of entity it describes, as list-entity-types gives it. Required when applies_to is entity.'),
        ];
    }
}
