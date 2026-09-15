<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateField;
use App\Actions\DestroyField;
use App\Actions\MakeSectionVersionEditable;
use App\Actions\UpdateField;
use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\RepetitionOrderEnum;
use App\Exceptions\CannotNestARepeater;
use App\Http\Resources\FieldResource;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\ValueList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class FieldController extends TemplateScopedController
{
    public function create(Request $request, string $template, string $version, string $section): JsonResponse
    {
        $row = $this->findRow($request, $template, $version, $section);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(FieldTypeEnum::class)],
            'label' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:255'],
            'value_list_id' => [
                'nullable',
                Rule::exists('value_lists', 'id')->where('account_id', $request->user()->account_id),
            ],
            'relation_target' => ['nullable', Rule::enum(RelationTargetEnum::class)],
            'relation_entity_type_id' => [
                'nullable',
                'string',
                Rule::exists('entity_types', 'uuid')->where('account_id', $request->user()->account_id),
            ],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $sectionVersion = new MakeSectionVersionEditable(
            user: $request->user(),
            row: $row,
        )->execute();

        try {
            $field = new CreateField(
                user: $request->user(),
                sectionVersion: $sectionVersion,
                type: FieldTypeEnum::from($validated['type']),
                label: $validated['label'],
                key: $validated['key'] ?? null,
                parent: $this->parent($sectionVersion, $validated['parent_id'] ?? null),
                valueList: $this->valueList($request, $validated['value_list_id'] ?? null),
                relationTarget: isset($validated['relation_target'])
                    ? RelationTargetEnum::from($validated['relation_target'])
                    : null,
                relationEntityType: $this->entityType($request, $validated['relation_entity_type_id'] ?? null),
            )->execute();
        } catch (CannotNestARepeater|InvalidArgumentException $e) {
            throw ValidationException::withMessages(['type' => $e->getMessage()]);
        }

        return new FieldResource($field)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $template, string $version, string $section, string $field): JsonResponse
    {
        $row = $this->findRow($request, $template, $version, $section);
        $key = $this->keyOf($row->sectionVersion, $field);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'help_text' => ['present', 'nullable', 'string', 'max:65535'],
            'placeholder' => ['present', 'nullable', 'string', 'max:255'],
            'is_required' => ['nullable', 'boolean'],
            'is_sensitive' => ['nullable', 'boolean'],
            'is_highlighted' => ['nullable', 'boolean'],
            'is_unique' => ['nullable', 'boolean'],
            'is_identifying' => ['nullable', 'boolean'],
            'value_list_id' => [
                'nullable',
                Rule::exists('value_lists', 'id')->where('account_id', $request->user()->account_id),
            ],
            'default_value' => ['nullable', 'array'],
            'options' => ['nullable', 'array'],
            'validation' => ['nullable', 'array'],
            'repeat_min' => ['nullable', 'integer', 'min:0'],
            'repeat_max' => ['nullable', 'integer', 'min:1'],
            'repetition_order' => ['nullable', Rule::enum(RepetitionOrderEnum::class)],
        ]);

        $sectionVersion = new MakeSectionVersionEditable(user: $request->user(), row: $row)->execute();
        $field = $this->fieldIn($sectionVersion, $key);

        try {
            new UpdateField(
                user: $request->user(),
                field: $field,
                label: $validated['label'],
                helpText: $validated['help_text'],
                placeholder: $validated['placeholder'],
                isRequired: $request->boolean('is_required'),
                isSensitive: $request->boolean('is_sensitive'),
                isHighlighted: $request->boolean('is_highlighted'),
                isUnique: $request->boolean('is_unique'),
                isIdentifying: $request->boolean('is_identifying'),
                valueList: $this->valueList($request, $validated['value_list_id'] ?? null),
                defaultValue: $validated['default_value'] ?? null,
                options: $validated['options'] ?? null,
                validation: $validated['validation'] ?? null,
                repeatMin: $validated['repeat_min'] ?? null,
                repeatMax: $validated['repeat_max'] ?? null,
                repetitionOrder: isset($validated['repetition_order'])
                    ? RepetitionOrderEnum::from($validated['repetition_order'])
                    : null,
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['value_list_id' => $e->getMessage()]);
        }

        return new FieldResource($field->refresh())->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $template, string $version, string $section, string $field): Response
    {
        $row = $this->findRow($request, $template, $version, $section);
        $key = $this->keyOf($row->sectionVersion, $field);

        $sectionVersion = new MakeSectionVersionEditable(user: $request->user(), row: $row)->execute();

        new DestroyField(
            user: $request->user(),
            field: $this->fieldIn($sectionVersion, $key),
        )->execute();

        return response()->noContent(204);
    }

    /**
     * The key of the field the caller named. Drafting a published section copies
     * its fields, which gives them new ids and keeps their keys, so the key is
     * what survives the draft and lets the same field be found on the other side.
     */
    private function keyOf(SectionVersion $sectionVersion, string $id): string
    {
        return $sectionVersion->fields()->whereKey($id)->firstOrFail()->key;
    }

    private function fieldIn(SectionVersion $sectionVersion, string $key): Field
    {
        return $sectionVersion->fields()->where('key', $key)->firstOrFail();
    }

    private function parent(SectionVersion $sectionVersion, ?int $id): ?Field
    {
        return $id === null ? null : $sectionVersion->fields()->whereKey($id)->firstOrFail();
    }

    private function valueList(Request $request, ?int $id): ?ValueList
    {
        return $id === null ? null : $request->user()->account->valueLists()->whereKey($id)->firstOrFail();
    }

    private function entityType(Request $request, ?string $uuid): ?EntityType
    {
        return $uuid === null ? null : $request->user()->account->entityTypes()->where('uuid', $uuid)->firstOrFail();
    }
}
