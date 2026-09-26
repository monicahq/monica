<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\RemoveSectionFromStructureVersion;
use App\Exceptions\PublishedVersionIsFrozen;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Remove a section from a draft of a template. The section itself stays in the account for the other templates using it. The change is written to the account journal.')]
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

        $version = $this->version($this->template($request, $validated['template']), (int) $validated['version']);

        try {
            new RemoveSectionFromStructureVersion(
                user: $this->user($request),
                row: $this->section($version, $validated['section']),
            )->execute();
        } catch (PublishedVersionIsFrozen $e) {
            return Response::error($e->getMessage());
        }

        return Response::text(__('The section is removed.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template.')->required(),
            'version' => $schema->integer()->description('The number of the draft.')->required(),
            'section' => $schema->string()->description('The id of the section in that version, as get-template-version gives it.')->required(),
        ];
    }
}
