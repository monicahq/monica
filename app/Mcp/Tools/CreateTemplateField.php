<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a field to a section of a draft of a template. A section shared with other templates is drafted first, so its fields may come back with new ids. The change is written to the account log.')]
class CreateTemplateField extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.sections.fields.create';

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
            'type' => $schema->string()->enum(array_column(FieldTypeEnum::cases(), 'value'))->description('The kind of field.')->required(),
            'label' => $schema->string()->description('What the field is called on screen.')->required(),
            'key' => $schema->string()->nullable()->description('A stable machine name for the field. Made from the label when left out.'),
            'value_list_id' => $schema->integer()->nullable()->description('The id of the value list a field offering choices takes them from, as list-value-lists gives it.'),
            'relation_target' => $schema->string()->nullable()->enum(array_column(RelationTargetEnum::cases(), 'value'))->description('What a relation field points at.'),
            'relation_entity_type_id' => $schema->string()->nullable()->description('The id of the kind of entity a relation field points at, as list-entity-types gives it.'),
            'parent_id' => $schema->integer()->nullable()->description('The id of the repeater field to put this field inside, as get-template-version gives it.'),
        ];
    }
}
