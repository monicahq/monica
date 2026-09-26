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

#[Description('Read one version of a template with its pages, the sections on each page and the fields of each section. The section and field ids the other template tools take come from here.')]
#[IsReadOnly]
class GetTemplateVersion extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        $version = $this->version($this->template($request, $validated['template']), (int) $validated['version'])
            ->load([
                'structure.owner',
                'previousVersion',
                'pages.assembly.sectionVersion.section',
                'pages.assembly.sectionVersion.fields',
            ]);

        return Response::json(new StructureVersionResource($version)->withStructure()->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template, as list-templates gives it.')->required(),
            'version' => $schema->integer()->description('The version number, as list-template-versions gives it.')->required(),
        ];
    }
}
