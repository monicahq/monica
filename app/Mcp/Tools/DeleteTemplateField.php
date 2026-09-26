<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyField;
use App\Actions\MakeSectionVersionEditable;
use App\Exceptions\PublishedVersionIsFrozen;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a field from a section of a draft of a template. The change is written to the account journal.')]
#[IsDestructive]
class DeleteTemplateField extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.sections.fields.destroy';

    public function handle(Request $request): Response
    {
        $user = $this->user($request);

        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'section' => ['required', 'string'],
            'field' => ['required', 'string'],
        ]);

        $version = $this->version($this->template($request, $validated['template']), (int) $validated['version']);
        $row = $this->section($version, $validated['section']);
        $key = $this->field($row->sectionVersion, $validated['field'])->key;

        try {
            $sectionVersion = new MakeSectionVersionEditable(user: $user, row: $row)->execute();

            new DestroyField(
                user: $user,
                field: $sectionVersion->fields()->where('key', $key)->firstOrFail(),
            )->execute();
        } catch (PublishedVersionIsFrozen $e) {
            return Response::error($e->getMessage());
        }

        return Response::text(__('The field is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template.')->required(),
            'version' => $schema->integer()->description('The number of the draft.')->required(),
            'section' => $schema->string()->description('The id of the section in that version, as get-template-version gives it.')->required(),
            'field' => $schema->string()->description('The id of the field, as get-template-version gives it.')->required(),
        ];
    }
}
