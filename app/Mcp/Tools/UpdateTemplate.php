<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateTemplate as UpdateTemplateAction;
use App\Http\Resources\TemplateResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a template or change its description. Anything left out keeps its value. The change is written to the account journal.')]
class UpdateTemplate extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
        ]);

        $template = $this->template($request, $validated['template']);

        new UpdateTemplateAction(
            user: $this->user($request),
            template: $template,
            name: $validated['name'] ?? $template->name,
            description: array_key_exists('description', $validated) ? $validated['description'] : $template->description,
        )->execute();

        return Response::json(new TemplateResource($template->refresh()->load(['structure.entityType', 'publishedVersion']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template, as list-templates gives it.')->required(),
            'name' => $schema->string()->description('The new name.'),
            'description' => $schema->string()->description('The new description. Null clears it.'),
        ];
    }
}
