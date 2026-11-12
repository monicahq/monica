<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossessionField as CreatePossessionFieldAction;
use App\Enums\PossessionFieldKindEnum;
use App\Http\Resources\PossessionTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a field to a possession type, in one of its groups or standing alone, placed last. A choice field takes its choices in order. The change is written to the account journal.')]
class CreatePossessionField extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionFields.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
            'group' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(PossessionFieldKindEnum::class)],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:255'],
        ]);

        $possessionType = $this->possessionType($request, $validated['possession_type']);

        new CreatePossessionFieldAction(
            user: $this->user($request),
            possessionType: $possessionType,
            group: isset($validated['group']) ? $this->possessionFieldGroup($possessionType, $validated['group']) : null,
            name: $validated['name'],
            kind: PossessionFieldKindEnum::from($validated['kind']),
            options: array_values($validated['options'] ?? []),
        )->execute();

        return Response::json(new PossessionTypeResource($possessionType)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The identifier of the possession type, as list-possession-types gives it.')->required(),
            'group' => $schema->string()->description('The identifier of the group it goes in, as get-possession-type gives it. Left out, the field stands alone.'),
            'name' => $schema->string()->description('What the field is called, such as Serial number.')->required(),
            'kind' => $schema->string()->enum(array_column(PossessionFieldKindEnum::cases(), 'value'))->description('What the field holds.')->required(),
            'options' => $schema->array()->items($schema->string())->description('The choices of a choice field, in order. Ignored for every other kind.'),
        ];
    }
}
