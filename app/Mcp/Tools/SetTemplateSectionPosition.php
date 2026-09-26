<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateStructureVersionSectionPosition;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Resources\StructureVersionSectionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a section to another place on its page, in a draft of a template. The change is written to the account journal.')]
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
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $version = $this->version($this->template($request, $validated['template']), (int) $validated['version']);
        $row = $this->section($version, $validated['section']);

        try {
            new UpdateStructureVersionSectionPosition(
                user: $this->user($request),
                row: $row,
                position: (int) $validated['position'],
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
            'position' => $schema->integer()->description('Where it goes on its page, counting from 1.')->required(),
        ];
    }
}
