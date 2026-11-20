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
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Move a section to another place on its page, in a draft of a template. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class SetTemplateSectionPosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.sections.position.update';

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

        return $this->answerThroughApi($request, $template->uuid, (string) $version->version_number, (string) $row->id);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The id of the template, as list-templates gives it.')->required(),
            'version' => $schema->integer()->description('The number of the draft, as create-template-draft gives it.')->required(),
            'section' => $schema->string()->description('The id of the section in that version, as get-template-version gives it.')->required(),
            'position' => $schema->integer()->description('Where the section goes on its page, counting from 1.')->required(),
        ];
    }
}
