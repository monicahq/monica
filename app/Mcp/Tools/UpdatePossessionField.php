<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionField as UpdatePossessionFieldAction;
use App\Enums\PossessionFieldKindEnum;
use App\Http\Resources\PossessionTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a field of a possession type, change its kind, or set its choices. What you leave out keeps its value. A field that stops being a choice field loses its choices. The change is written to the account journal.')]
class UpdatePossessionField extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionFields.update';

    public function handle(Request $request): Response
    {
        $address = $request->validate([
            'possession_type' => ['required', 'string'],
            'field' => ['required', 'string'],
        ]);

        $possessionType = $this->possessionType($request, $address['possession_type']);
        $field = $this->possessionField($possessionType, $address['field']);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'kind' => ['sometimes', 'required', Rule::enum(PossessionFieldKindEnum::class)],
            'options' => ['sometimes', 'nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:255'],
        ]);

        new UpdatePossessionFieldAction(
            user: $this->user($request),
            field: $field,
            name: $validated['name'] ?? $field->name,
            kind: isset($validated['kind']) ? PossessionFieldKindEnum::from($validated['kind']) : $field->kind,
            options: array_key_exists('options', $validated) ? array_values($validated['options'] ?? []) : $field->options,
        )->execute();

        return Response::json(new PossessionTypeResource($possessionType)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The identifier of the possession type, as list-possession-types gives it.')->required(),
            'field' => $schema->string()->description('The identifier of the field, as get-possession-type gives it.')->required(),
            'name' => $schema->string()->description('The new name.'),
            'kind' => $schema->string()->enum(array_column(PossessionFieldKindEnum::cases(), 'value'))->description('What the field holds.'),
            'options' => $schema->array()->items($schema->string())->nullable()->description('The choices of a choice field, in order, replacing the ones it has. Null clears them.'),
        ];
    }
}
