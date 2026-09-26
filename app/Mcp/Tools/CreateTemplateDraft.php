<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateStructureVersionDraft;
use App\Http\Resources\StructureVersionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Open a draft of a template, the only kind of version whose sections and fields can change. When a draft is already open, it is returned instead of a second one.')]
class CreateTemplateDraft extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
        ]);

        $template = $this->template($request, $validated['template']);

        $startingPoint = $template->structure->publishedVersion ?? $template->versions()->first();

        if ($startingPoint === null) {
            return Response::error('This template has no version to start from.');
        }

        $draft = new CreateStructureVersionDraft(
            user: $this->user($request),
            structureVersion: $startingPoint,
        )->execute();

        return Response::json(new StructureVersionResource($draft->load(['structure.owner', 'previousVersion']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template, as list-templates gives it.')->required(),
        ];
    }
}
