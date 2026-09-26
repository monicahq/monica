<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateTemplatePosition;
use App\Http\Resources\TemplateResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a template to another place in the list of templates. The change is written to the account journal.')]
class SetTemplatePosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.position.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $template = $this->template($request, $validated['template']);

        new UpdateTemplatePosition(
            user: $this->user($request),
            template: $template,
            position: (int) $validated['position'],
        )->execute();

        return Response::json(new TemplateResource($template->refresh()->load(['structure.entityType', 'publishedVersion']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template, as list-templates gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes in the list, counting from 1.')->required(),
        ];
    }
}
