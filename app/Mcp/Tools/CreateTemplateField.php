<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateField;
use App\Actions\MakeSectionVersionEditable;
use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Exceptions\CannotNestARepeater;
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

#[Description('Add a field to a section of a draft of a template. A section shared with other templates is drafted first, so its fields may come back with new ids. The change is written to the account journal.')]
class CreateTemplateField extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.sections.fields.create';

    public function handle(Request $request): Response
    {
        $user = $this->user($request);

        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'section' => ['required', 'string'],
            'type' => ['required', Rule::enum(FieldTypeEnum::class)],
            'label' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:255'],
            'value_list_id' => [
                'nullable',
                Rule::exists('value_lists', 'id')->where('account_id', $user->account_id),
            ],
            'relation_target' => ['nullable', Rule::enum(RelationTargetEnum::class)],
            'relation_entity_type_id' => [
                'nullable',
                'string',
                Rule::exists('entity_types', 'uuid')->where('account_id', $user->account_id)->where('is_active', true),
            ],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $version = $this->version($this->template($request, $validated['template']), (int) $validated['version']);
        $row = $this->section($version, $validated['section']);

        try {
            $sectionVersion = new MakeSectionVersionEditable(user: $user, row: $row)->execute();

            $field = new CreateField(
                user: $user,
                sectionVersion: $sectionVersion,
                type: FieldTypeEnum::from($validated['type']),
                label: $validated['label'],
                key: $validated['key'] ?? null,
                parent: isset($validated['parent_id']) ? $this->field($sectionVersion, (string) $validated['parent_id']) : null,
                valueList: isset($validated['value_list_id'])
                    ? $user->account->valueLists()->whereKey($validated['value_list_id'])->firstOrFail()
                    : null,
                relationTarget: isset($validated['relation_target'])
                    ? RelationTargetEnum::from($validated['relation_target'])
                    : null,
                relationEntityType: isset($validated['relation_entity_type_id'])
                    ? $user->account->entityTypes()->where('uuid', $validated['relation_entity_type_id'])->firstOrFail()
                    : null,
            )->execute();
        } catch (CannotNestARepeater|InvalidArgumentException|PublishedVersionIsFrozen $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new FieldResource($field)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template.')->required(),
            'version' => $schema->integer()->description('The number of the draft.')->required(),
            'section' => $schema->string()->description('The id of the section in that version, as get-template-version gives it.')->required(),
            'type' => $schema->string()->description('The kind of field: '.implode(', ', array_column(FieldTypeEnum::cases(), 'value')).'.')->required(),
            'label' => $schema->string()->description('What the field is called on screen.')->required(),
            'key' => $schema->string()->description('A stable machine name for the field. Made from the label when left out.'),
            'value_list_id' => $schema->integer()->description('The value list a field offering choices takes them from.'),
            'relation_target' => $schema->string()->description('What a relation field points at: '.implode(', ', array_column(RelationTargetEnum::cases(), 'value')).'.'),
            'relation_entity_type_id' => $schema->string()->description('The kind of entity a relation field points at, as list-entity-types gives it.'),
            'parent_id' => $schema->integer()->description('The id of the repeater field to put this field inside.'),
        ];
    }
}
