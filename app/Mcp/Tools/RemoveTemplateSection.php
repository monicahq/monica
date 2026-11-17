<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Remove a section from a draft of a template. The section itself stays in the account for the other templates using it. The change is written to the account log.')]
#[IsDestructive]
class RemoveTemplateSection extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.sections.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'section' => ['required', 'string'],
        ]);

        $template = $this->template($request, $validated['template']);
        $version = $this->version($template, (int) $validated['version']);
        $row = $this->section($version, $validated['section']);

        $answer = $this->answerThroughApi($request, $template->uuid, (string) $version->version_number, (string) $row->id);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The section is removed.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The id of the template, as list-templates gives it.')->required(),
            'version' => $schema->integer()->description('The number of the draft, as create-template-draft gives it.')->required(),
            'section' => $schema->string()->description('The id of the section in that version, as get-template-version gives it.')->required(),
        ];
    }
}
