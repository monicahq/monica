<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\MakeSectionVersionEditable;
use App\Actions\UpdateField;
use App\Enums\RepetitionOrderEnum;
use App\Exceptions\AcceptedFileKindIsNotValid;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Resources\FieldResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change a field of a section in a draft of a template. Anything left out keeps its value. The field may come back with a new id: use the returned one afterwards. The change is written to the account journal.')]
class UpdateTemplateField extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.sections.fields.update';

    public function handle(Request $request): Response
    {
        $user = $this->user($request);

        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'section' => ['required', 'string'],
            'field' => ['required', 'string'],
            'label' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:65535'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'is_required' => ['nullable', 'boolean'],
            'is_sensitive' => ['nullable', 'boolean'],
            'is_highlighted' => ['nullable', 'boolean'],
            'is_unique' => ['nullable', 'boolean'],
            'is_identifying' => ['nullable', 'boolean'],
            'value_list_id' => [
                'nullable',
                Rule::exists('value_lists', 'id')->where('account_id', $user->account_id),
            ],
            'default_value' => ['nullable', 'array'],
            'options' => ['nullable', 'array'],
            'validation' => ['nullable', 'array'],
            'repeat_min' => ['nullable', 'integer', 'min:0'],
            'repeat_max' => ['nullable', 'integer', 'min:1'],
            'repetition_order' => ['nullable', Rule::enum(RepetitionOrderEnum::class)],
        ]);

        $version = $this->version($this->template($request, $validated['template']), (int) $validated['version']);
        $row = $this->section($version, $validated['section']);
        $key = $this->field($row->sectionVersion, $validated['field'])->key;

        try {
            $sectionVersion = new MakeSectionVersionEditable(user: $user, row: $row)->execute();
            $field = $sectionVersion->fields()->where('key', $key)->firstOrFail();

            $given = fn (string $name): bool => array_key_exists($name, $validated);

            new UpdateField(
                user: $user,
                field: $field,
                label: $validated['label'] ?? $field->label,
                helpText: $given('help_text') ? $validated['help_text'] : $field->help_text,
                placeholder: $given('placeholder') ? $validated['placeholder'] : $field->placeholder,
                isRequired: (bool) ($validated['is_required'] ?? $field->is_required),
                isSensitive: (bool) ($validated['is_sensitive'] ?? $field->is_sensitive),
                isHighlighted: (bool) ($validated['is_highlighted'] ?? $field->is_highlighted),
                isUnique: (bool) ($validated['is_unique'] ?? $field->is_unique),
                isIdentifying: (bool) ($validated['is_identifying'] ?? $field->is_identifying),
                valueList: $given('value_list_id')
                    ? $user->account->valueLists()->whereKey($validated['value_list_id'])->first()
                    : $field->valueList,
                defaultValue: $given('default_value') ? $validated['default_value'] : $field->default_value,
                options: $given('options') ? $validated['options'] : $field->options,
                validation: $given('validation') ? $validated['validation'] : $field->validation,
                repeatMin: $given('repeat_min') ? $validated['repeat_min'] : $field->repeat_min,
                repeatMax: $given('repeat_max') ? $validated['repeat_max'] : $field->repeat_max,
                repetitionOrder: $given('repetition_order')
                    ? RepetitionOrderEnum::tryFrom((string) $validated['repetition_order'])
                    : $field->repetition_order,
            )->execute();
        } catch (InvalidArgumentException|AcceptedFileKindIsNotValid|PublishedVersionIsFrozen $e) {
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
            'label' => $schema->string()->description('What the field is called on screen.'),
            'help_text' => $schema->string()->description('A line explaining the field, shown under it.'),
            'placeholder' => $schema->string()->description('The example shown in the empty field.'),
            'is_required' => $schema->boolean()->description('Whether a record cannot be saved without it.'),
            'is_sensitive' => $schema->boolean()->description('Whether its value is hidden until asked for.'),
            'is_highlighted' => $schema->boolean()->description('Whether it is shown at the top of the record.'),
            'is_unique' => $schema->boolean()->description('Whether two records may not share a value.'),
            'is_identifying' => $schema->boolean()->description('Whether it helps tell records apart.'),
            'value_list_id' => $schema->integer()->description('The value list a field offering choices takes them from.'),
            'default_value' => $schema->object()->description('The value a new record starts with.'),
            'options' => $schema->object()->description('Settings of the field type, such as the kinds of file accepted.'),
            'validation' => $schema->object()->description('Limits on the value, such as a minimum or a maximum.'),
            'repeat_min' => $schema->integer()->description('The fewest values a repeatable field holds.'),
            'repeat_max' => $schema->integer()->description('The most values a repeatable field holds.'),
            'repetition_order' => $schema->string()->description('How repeated values are ordered: '.implode(', ', array_column(RepetitionOrderEnum::cases(), 'value')).'.'),
        ];
    }
}
