<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a template to another place in the list of templates. The change is written to the account log.')]
class SetTemplatePosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.position.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
        ]);

        $template = $this->template($request, $validated['template']);

        return $this->answerThroughApi($request, $template->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The id of the template, as list-templates gives it.')->required(),
            'position' => $schema->integer()->description('Where the template goes in the list, counting from 1.')->required(),
        ];
    }
}
