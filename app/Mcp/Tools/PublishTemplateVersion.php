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

#[Description('Publish a draft of a template, so the records it describes start using it. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class PublishTemplateVersion extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.publication.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        $template = $this->template($request, $validated['template']);
        $version = $this->version($template, (int) $validated['version']);

        return $this->answerThroughApi($request, $template->uuid, (string) $version->version_number);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The id of the template, as list-templates gives it.')->required(),
            'version' => $schema->integer()->description('The number of the draft to publish, as list-template-versions gives it.')->required(),
            'change_note' => $schema->string()->nullable()->description('What changed. Required from the second published version on.'),
        ];
    }
}
