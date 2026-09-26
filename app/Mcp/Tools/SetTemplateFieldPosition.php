<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\MakeSectionVersionEditable;
use App\Actions\UpdateFieldPosition;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Resources\FieldResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a field to another place in its section, in a draft of a template. The field may come back with a new id: use the returned one afterwards. The change is written to the account journal.')]
class SetTemplateFieldPosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.sections.fields.position.update';

    public function handle(Request $request): Response
    {
        $user = $this->user($request);

        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'section' => ['required', 'string'],
            'field' => ['required', 'string'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $version = $this->version($this->template($request, $validated['template']), (int) $validated['version']);
        $row = $this->section($version, $validated['section']);
        $key = $this->field($row->sectionVersion, $validated['field'])->key;

        try {
            $sectionVersion = new MakeSectionVersionEditable(user: $user, row: $row)->execute();
            $field = $sectionVersion->fields()->where('key', $key)->firstOrFail();

            new UpdateFieldPosition(
                user: $user,
                field: $field,
                position: (int) $validated['position'],
            )->execute();
        } catch (PublishedVersionIsFrozen $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new FieldResource($field->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template.')->required(),
            'version' => $schema->integer()->description('The number of the draft.')->required(),
            'section' => $schema->string()->description('The id of the section in that version, as get-template-version gives it.')->required(),
            'field' => $schema->string()->description('The id of the field, as get-template-version gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes in its section, counting from 1.')->required(),
        ];
    }
}
