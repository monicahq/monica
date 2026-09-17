<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ReplaceActivityTypeFields;
use App\Enums\FieldTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\StructureVersionSectionResource;
use App\Models\ActivityType;
use App\Models\StructureVersion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ActivityTypeFieldController extends Controller
{
    private const array COUNTED_SETTINGS = [
        'max_length',
        'min',
        'max',
        'decimals',
        'max_choices',
        'repeat_min',
        'repeat_max',
    ];

    public function index(Request $request, string $activityType): JsonResponse
    {
        $version = $this->publishedVersion($this->find($request, $activityType));

        return response()->json([
            'data' => $version === null
                ? []
                : StructureVersionSectionResource::collection($version->assembly)->resolve(),
        ]);
    }

    public function update(Request $request, string $activityType): JsonResponse
    {
        $type = $this->find($request, $activityType);

        $request->validate($this->rules());

        try {
            new ReplaceActivityTypeFields(
                user: $request->user(),
                activityType: $type,
                groups: $this->groups($request),
                fields: $this->fields($request),
            )->execute();
        } catch (ModelNotFoundException $e) {
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
     * The groups as the action wants them, each named by where it sits in what was sent, since a
     * caller describes a group by holding its fields rather than by inventing a reference for it.
     *
     * @return list<array{reference: string, placement_id: ?int, name: string}>
     */
    private function groups(Request $request): array
    {
        $groups = [];

        foreach (array_values((array) $request->input('groups', [])) as $index => $group) {
            $groups[] = [
                'reference' => 'g'.$index,
                'placement_id' => ($group['placement_id'] ?? null) === null ? null : (int) $group['placement_id'],
                'name' => (string) $group['name'],
            ];
        }

        return $groups;
    }

    /**
     * Every field of every group, then the ones nobody grouped, which the action files under the
     * empty reference.
     *
     * @return list<array{key: ?string, group: string, label: string, type: string, is_required: bool, help_text: ?string, settings: array<string, string>, choices: list<string>}>
     */
    private function fields(Request $request): array
    {
        $fields = [];

        foreach (array_values((array) $request->input('groups', [])) as $index => $group) {
            foreach ((array) ($group['fields'] ?? []) as $field) {
                $fields[] = $this->field($field, 'g'.$index);
            }
        }

        foreach ((array) $request->input('fields', []) as $field) {
            $fields[] = $this->field($field, '');
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array{key: ?string, group: string, label: string, type: string, is_required: bool, help_text: ?string, settings: array<string, string>, choices: list<string>}
     */
    private function field(array $field, string $group): array
    {
        return [
            'key' => ($field['key'] ?? null) === null ? null : (string) $field['key'],
            'group' => $group,
            'label' => (string) $field['label'],
            'type' => (string) $field['type'],
            'is_required' => (bool) ($field['is_required'] ?? false),
            'help_text' => ($field['help_text'] ?? null) === null ? null : (string) $field['help_text'],
            'settings' => array_map(strval(...), array_filter((array) ($field['settings'] ?? []), fn (mixed $value): bool => $value !== null)),
            'choices' => array_values(array_filter(
                array_map(fn (mixed $choice): string => trim((string) $choice), (array) ($field['choices'] ?? [])),
                fn (string $choice): bool => $choice !== '',
            )),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        $rules = [
            'groups' => ['nullable', 'array'],
            'groups.*.placement_id' => ['nullable', 'integer'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.fields' => ['nullable', 'array'],
            'fields' => ['nullable', 'array'],
        ];

        foreach (['groups.*.fields.*', 'fields.*'] as $path) {
            $rules["{$path}.key"] = ['nullable', 'string', 'max:64'];
            $rules["{$path}.label"] = ['required', 'string', 'max:255'];
            $rules["{$path}.type"] = ['required', Rule::enum(FieldTypeEnum::class)];
            $rules["{$path}.is_required"] = ['nullable', 'boolean'];
            $rules["{$path}.help_text"] = ['nullable', 'string', 'max:255'];
            $rules["{$path}.settings"] = ['nullable', 'array'];
            $rules["{$path}.settings.*"] = ['nullable', 'string', 'max:255'];
            $rules["{$path}.choices"] = ['nullable', 'array'];
            $rules["{$path}.choices.*"] = ['nullable', 'string', 'max:255'];

            foreach (self::COUNTED_SETTINGS as $setting) {
                $rules["{$path}.settings.{$setting}"] = ['nullable', 'integer', 'min:0'];
            }
        }

        return $rules;
    }

    private function publishedVersion(ActivityType $activityType): ?StructureVersion
    {
        return $activityType->structure?->publishedVersion;
    }

    private function find(Request $request, string $uuid): ActivityType
    {
        return $request->user()->account->activityTypes()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
