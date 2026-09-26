<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\TemplateResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one template of the account: its name, what it describes, whether it is active and which version is published.')]
#[IsReadOnly]
class GetTemplate extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
        ]);

        $template = $this->template($request, $validated['template'])->load(['structure.entityType', 'publishedVersion']);

        return Response::json(new TemplateResource($template)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template, as list-templates gives it.')->required(),
        ];
    }
}
