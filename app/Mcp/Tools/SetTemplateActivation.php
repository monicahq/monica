<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\ActivateTemplate;
use App\Actions\DeactivateTemplate;
use App\Http\Resources\TemplateResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Switch a template on, so it is offered for new records, or off. The change is written to the account journal.')]
class SetTemplateActivation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.activation.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $template = $this->template($request, $validated['template']);

        if ($validated['is_active']) {
            new ActivateTemplate(user: $this->user($request), template: $template)->execute();
        }

        if (! $validated['is_active']) {
            new DeactivateTemplate(user: $this->user($request), template: $template)->execute();
        }

        return Response::json(new TemplateResource($template->refresh()->load(['structure.entityType', 'publishedVersion']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template, as list-templates gives it.')->required(),
            'is_active' => $schema->boolean()->description('True to switch it on, false to switch it off.')->required(),
        ];
    }
}
