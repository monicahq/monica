<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\StructureVersionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the versions of one template, so a caller can tell a draft from what is published.')]
#[IsReadOnly]
class ListTemplateVersions extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
        ]);

        $template = $this->template($request, $validated['template']);

        $versions = $template->versions()
            ->with(['structure.owner', 'previousVersion'])
            ->get();

        return Response::json(StructureVersionResource::collection($versions)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()
                ->description('The identifier of the template, as list-templates gives it.')
                ->required(),
        ];
    }
}
