<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DuplicateTemplate as DuplicateTemplateAction;
use App\Http\Resources\TemplateResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Copy a template into a new one, as a starting point. The change is written to the account journal.')]
class DuplicateTemplate extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.duplicate.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $copy = new DuplicateTemplateAction(
            user: $this->user($request),
            template: $this->template($request, $validated['template']),
            name: $validated['name'] ?? null,
            slug: $validated['slug'] ?? null,
        )->execute();

        return Response::json(new TemplateResource($copy->load(['structure.entityType', 'publishedVersion']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template, as list-templates gives it.')->required(),
            'name' => $schema->string()->description('The name of the copy. Made from the original when left out.'),
            'slug' => $schema->string()->description('The short name of the copy. Made from its name when left out.'),
        ];
    }
}
