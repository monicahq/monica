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
use App\Exceptions\AcceptedFileKindIsNotValid;
use App\Exceptions\CannotNestARepeater;
use App\Exceptions\PublishedVersionIsFrozen;
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
                'bail',
                'nullable',
                'string',
                'bail',
                'uuid',
                Rule::exists('entity_types', 'uuid')->where('account_id', $request->user()->account_id)->where('is_active', true),
            ],
            'parent_id' => ['nullable', 'integer'],
        ]);

        try {
            $sectionVersion = new MakeSectionVersionEditable(
                user: $request->user(),
                row: $row,
            )->execute();

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
        } catch (PublishedVersionIsFrozen $e) {
            throw ValidationException::withMessages(['version' => $e->getMessage()]);
        }

        return new FieldResource($field)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $template, string $version, string $section, string $field): JsonResponse
    {
        $row = $this->findRow($request, $template, $version, $section);
        $key = $this->keyOf($row->sectionVersion, $field);

        $validated = $request->validate([
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'help_text' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'placeholder' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
            'is_sensitive' => ['sometimes', 'boolean'],
            'is_highlighted' => ['sometimes', 'boolean'],
            'is_unique' => ['sometimes', 'boolean'],
            'is_identifying' => ['sometimes', 'boolean'],
            'value_list_id' => [
                'sometimes',
                'nullable',
                Rule::exists('value_lists', 'id')->where('account_id', $request->user()->account_id),
            ],
            'default_value' => ['sometimes', 'nullable', 'array'],
            'options' => ['sometimes', 'nullable', 'array'],
            'validation' => ['sometimes', 'nullable', 'array'],
            'repeat_min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'repeat_max' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'repetition_order' => ['sometimes', 'nullable', Rule::enum(RepetitionOrderEnum::class)],
        ]);

        try {
            $sectionVersion = new MakeSectionVersionEditable(user: $request->user(), row: $row)->execute();
            $field = $this->fieldIn($sectionVersion, $key);
            $repetitionOrder = $field->repetition_order;

            if (array_key_exists('repetition_order', $validated)) {
                $repetitionOrder = $validated['repetition_order'] === null ? null : RepetitionOrderEnum::from($validated['repetition_order']);
            }

            new UpdateField(
                user: $request->user(),
                field: $field,
                label: $validated['label'] ?? $field->label,
                helpText: array_key_exists('help_text', $validated) ? $validated['help_text'] : $field->help_text,
                placeholder: array_key_exists('placeholder', $validated) ? $validated['placeholder'] : $field->placeholder,
                isRequired: array_key_exists('is_required', $validated) ? (bool) $validated['is_required'] : $field->is_required,
                isSensitive: array_key_exists('is_sensitive', $validated) ? (bool) $validated['is_sensitive'] : $field->is_sensitive,
                isHighlighted: array_key_exists('is_highlighted', $validated) ? (bool) $validated['is_highlighted'] : $field->is_highlighted,
                isUnique: array_key_exists('is_unique', $validated) ? (bool) $validated['is_unique'] : $field->is_unique,
                isIdentifying: array_key_exists('is_identifying', $validated) ? (bool) $validated['is_identifying'] : $field->is_identifying,
                valueList: array_key_exists('value_list_id', $validated) ? $this->valueList($request, $validated['value_list_id']) : $field->valueList,
                defaultValue: array_key_exists('default_value', $validated) ? $validated['default_value'] : $field->default_value,
                options: array_key_exists('options', $validated) ? $validated['options'] : $field->options,
                validation: array_key_exists('validation', $validated) ? $validated['validation'] : $field->validation,
                repeatMin: array_key_exists('repeat_min', $validated) ? $validated['repeat_min'] : $field->repeat_min,
                repeatMax: array_key_exists('repeat_max', $validated) ? $validated['repeat_max'] : $field->repeat_max,
                repetitionOrder: $repetitionOrder,
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['value_list_id' => $e->getMessage()]);
        } catch (AcceptedFileKindIsNotValid $e) {
            throw ValidationException::withMessages(['options' => $e->getMessage()]);
        } catch (PublishedVersionIsFrozen $e) {
            throw ValidationException::withMessages(['version' => $e->getMessage()]);
        }

        return new FieldResource($field->refresh())->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $template, string $version, string $section, string $field): Response
    {
        $row = $this->findRow($request, $template, $version, $section);
        $key = $this->keyOf($row->sectionVersion, $field);

        try {
            $sectionVersion = new MakeSectionVersionEditable(user: $request->user(), row: $row)->execute();

            new DestroyField(
                user: $request->user(),
                field: $this->fieldIn($sectionVersion, $key),
            )->execute();
        } catch (PublishedVersionIsFrozen $e) {
            throw ValidationException::withMessages(['version' => $e->getMessage()]);
        }

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
