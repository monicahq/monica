<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\RepetitionOrderEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Change a field of a section in a draft of a template. The field may come back with a new id: use the returned one afterwards. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateTemplateField extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.sections.fields.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'section' => ['required', 'string'],
            'field' => ['required', 'string'],
        ]);

        $template = $this->template($request, $validated['template']);
        $version = $this->version($template, (int) $validated['version']);
        $row = $this->section($version, $validated['section']);
        $field = $this->field($row->sectionVersion, $validated['field']);

        return $this->answerThroughApi($request, $template->uuid, (string) $version->version_number, (string) $row->id, (string) $field->id);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The id of the template, as list-templates gives it.')->required(),
            'version' => $schema->integer()->description('The number of the draft, as create-template-draft gives it.')->required(),
            'section' => $schema->string()->description('The id of the section in that version, as get-template-version gives it.')->required(),
            'field' => $schema->string()->description('The id of the field, as get-template-version gives it.')->required(),
            'label' => $schema->string()->description('What the field is called on screen.'),
            'help_text' => $schema->string()->nullable()->description('A line explaining the field, shown under it.'),
            'placeholder' => $schema->string()->nullable()->description('The example shown in the empty field.'),
            'is_required' => $schema->boolean()->description('Whether a record cannot be saved without a value in this field.'),
            'is_sensitive' => $schema->boolean()->description('Whether the value is hidden until asked for.'),
            'is_highlighted' => $schema->boolean()->description('Whether the field is shown at the top of the record.'),
            'is_unique' => $schema->boolean()->description('Whether two records may not share a value.'),
            'is_identifying' => $schema->boolean()->description('Whether the field helps tell records apart.'),
            'value_list_id' => $schema->integer()->nullable()->description('The id of the value list a field offering choices takes them from, as list-value-lists gives it.'),
            'default_value' => $schema->object()->nullable()->description('The value a new record starts with.'),
            'options' => $schema->object()->nullable()->description('Settings of the field type, such as the kinds of file accepted.'),
            'validation' => $schema->object()->nullable()->description('Limits on the value, such as a minimum or a maximum.'),
            'repeat_min' => $schema->integer()->nullable()->description('The fewest values a repeatable field holds.'),
            'repeat_max' => $schema->integer()->nullable()->description('The most values a repeatable field holds.'),
            'repetition_order' => $schema->string()->nullable()->enum(RepetitionOrderEnum::values())->description('How repeated values are ordered.'),
        ];
    }
}
