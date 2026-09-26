<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateStructureVersionSection;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Resources\StructureVersionSectionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change what a section is headed in a draft of a template and whether it starts folded shut. Anything left out keeps its value. The change is written to the account journal.')]
class UpdateTemplateSection extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.sections.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'section' => ['required', 'string'],
            'title_override' => ['nullable', 'string', 'max:255'],
            'is_collapsed_by_default' => ['nullable', 'boolean'],
        ]);

        $version = $this->version($this->template($request, $validated['template']), (int) $validated['version']);
        $row = $this->section($version, $validated['section']);

        try {
            new UpdateStructureVersionSection(
                user: $this->user($request),
                row: $row,
                titleOverride: array_key_exists('title_override', $validated)
                    ? $validated['title_override']
                    : $row->title_override,
                isCollapsedByDefault: (bool) ($validated['is_collapsed_by_default'] ?? $row->is_collapsed_by_default),
            )->execute();
        } catch (PublishedVersionIsFrozen $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new StructureVersionSectionResource($row->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template.')->required(),
            'version' => $schema->integer()->description('The number of the draft.')->required(),
            'section' => $schema->string()->description('The id of the section in that version, as get-template-version gives it.')->required(),
            'title_override' => $schema->string()->description('What to head the section with here. Null goes back to its own name.'),
            'is_collapsed_by_default' => $schema->boolean()->description('Whether the section starts folded shut.'),
        ];
    }
}
