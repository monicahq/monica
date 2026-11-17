<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ReplaceLifeEventTypeFields;
use App\Exceptions\AcceptedFileKindIsNotValid;
use App\Helpers\FieldSettingsCatalogue;
use App\Helpers\LifeEventTypeFieldLayout;
use App\Http\Controllers\Controller;
use App\Http\Resources\StructureVersionSectionResource;
use App\Models\Field;
use App\Models\LifeEventType;
use App\Models\StructureVersion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LifeEventTypeFieldController extends Controller
{
    public function index(Request $request, string $lifeEventType): JsonResponse
    {
        $version = $this->publishedVersion($this->find($request, $lifeEventType));

        return response()->json([
            'data' => $version === null
                ? []
                : StructureVersionSectionResource::collection($version->assembly)->resolve(),
        ]);
    }

    public function update(Request $request, string $lifeEventType): JsonResponse
    {
        $type = $this->find($request, $lifeEventType);

        $validated = $request->validate(LifeEventTypeFieldLayout::rules());
        $stored = $this->storedLayout($type);

        $groups = array_key_exists('groups', $validated) ? (array) $request->input('groups') : $stored['groups'];
        $fields = array_key_exists('fields', $validated) ? (array) $request->input('fields') : $stored['fields'];

        try {
            new ReplaceLifeEventTypeFields(
                user: $request->user(),
                lifeEventType: $type,
                groups: LifeEventTypeFieldLayout::groups($groups),
                fields: LifeEventTypeFieldLayout::fields($groups, $fields),
            )->execute();
        } catch (ModelNotFoundException|AcceptedFileKindIsNotValid $e) {
            throw ValidationException::withMessages(['fields' => $e->getMessage()]);
        }

        $version = $this->publishedVersion($type->refresh());

        return response()->json([
            'data' => $version === null
                ? []
                : StructureVersionSectionResource::collection($version->assembly)->resolve(),
        ]);
    }

    /**
     * The layout published today, written the way a caller sends it, so a part the caller leaves
     * out is sent back unchanged.
     *
     * @return array{groups: list<array<string, mixed>>, fields: list<array<string, mixed>>}
     */
    private function storedLayout(LifeEventType $lifeEventType): array
    {
        $layout = ['groups' => [], 'fields' => []];

        foreach ($this->publishedVersion($lifeEventType)->assembly ?? [] as $row) {
            $fields = $row->sectionVersion->fields
                ->whereNull('parent_field_id')
                ->sortBy('position')
                ->map(fn (Field $field): array => [
                    'key' => $field->key,
                    'label' => $field->label,
                    'type' => $field->type->value,
                    'is_required' => $field->is_required,
                    'help_text' => $field->help_text,
                    'settings' => $this->storedSettings($field),
                    'choices' => $field->valueList?->items->sortBy('position')->pluck('label')->values()->all() ?? [],
                ])
                ->values()
                ->all();

            if (! $row->shows_title) {
                $layout['fields'] = $fields;

                continue;
            }

            $layout['groups'][] = [
                'placement_id' => $row->id,
                'name' => $row->title_override ?? $row->sectionVersion->section->name,
                'fields' => $fields,
            ];
        }

        return $layout;
    }

    /** @return array<string, string> */
    private function storedSettings(Field $field): array
    {
        $settings = [];

        foreach (FieldSettingsCatalogue::of($field->type) as $setting) {
            $settings[$setting['key']] = FieldSettingsCatalogue::read(
                key: $setting['key'],
                options: $field->options,
                column: match ($setting['key']) {
                    'placeholder' => $field->placeholder,
                    'repeat_min' => $field->repeat_min,
                    'repeat_max' => $field->repeat_max,
                    'repetition_order' => $field->repetition_order?->value,
                    'relation_target' => $field->relation_entity_type_id === null
                        ? $field->relation_target?->value
                        : $field->relation_target?->value.':'.$field->relation_entity_type_id,
                    default => null,
                },
            );
        }

        return $settings;
    }

    private function publishedVersion(LifeEventType $lifeEventType): ?StructureVersion
    {
        return $lifeEventType->structure?->publishedVersion;
    }

    private function find(Request $request, string $uuid): LifeEventType
    {
        return $request->user()->account->lifeEventTypes()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
