<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PossessionTypes;

use App\Actions\CreatePossessionField;
use App\Actions\DestroyPossessionField;
use App\Actions\UpdatePossessionField;
use App\Enums\PossessionFieldKindEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionTypeResource;
use App\Models\PossessionField;
use App\Models\PossessionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PossessionFieldController extends Controller
{
    public function create(Request $request, string $possessionType): JsonResponse
    {
        $type = $this->type($request, $possessionType);
        $validated = $request->validate([
            ...$this->rules(),
            'group_id' => ['bail', 'nullable', 'uuid', Rule::exists('possession_field_groups', 'uuid')->where('possession_type_id', $type->id)],
        ]);

        new CreatePossessionField(
            user: $request->user(),
            possessionType: $type,
            group: isset($validated['group_id']) ? $type->groups()->where('uuid', $validated['group_id'])->firstOrFail() : null,
            name: $validated['name'],
            kind: PossessionFieldKindEnum::from($validated['kind']),
            options: array_values($validated['options'] ?? []),
        )->execute();

        return new PossessionTypeResource($type)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $possessionType, string $field): JsonResponse
    {
        $type = $this->type($request, $possessionType);
        $record = $this->field($type, $field);
        $validated = $request->validate($this->rules(isCreating: false));

        new UpdatePossessionField(
            user: $request->user(),
            field: $record,
            name: $validated['name'] ?? $record->name,
            kind: isset($validated['kind']) ? PossessionFieldKindEnum::from($validated['kind']) : $record->kind,
            options: array_key_exists('options', $validated) ? array_values($validated['options'] ?? []) : $record->options,
        )->execute();

        return new PossessionTypeResource($type)->response();
    }

    public function destroy(Request $request, string $possessionType, string $field): Response
    {
        $type = $this->type($request, $possessionType);

        new DestroyPossessionField(
            user: $request->user(),
            field: $this->field($type, $field),
        )->execute();

        return response()->noContent(204);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(bool $isCreating = true): array
    {
        return [
            'name' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'kind' => [$isCreating ? 'required' : 'sometimes', Rule::enum(PossessionFieldKindEnum::class)],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function type(Request $request, string $uuid): PossessionType
    {
        return $request->user()->account->possessionTypes()->where('uuid', $uuid)->firstOrFail();
    }

    private function field(PossessionType $type, string $uuid): PossessionField
    {
        return $type->fields()->where('uuid', $uuid)->firstOrFail();
    }
}
