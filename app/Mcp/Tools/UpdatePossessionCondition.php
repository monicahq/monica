<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionCondition as UpdatePossessionConditionAction;
use App\Enums\VaultColourEnum;
use App\Http\Resources\PossessionConditionResource;
use App\Models\Section;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a possession condition, give it another vault colour, or set, change or remove its icon. What you leave out keeps its value; an empty icon removes it. It keeps its place in the order. The change is written to the account journal.')]
class UpdatePossessionCondition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionConditions.update';

    public function handle(Request $request): Response
    {
        $address = $request->validate([
            'possession_condition' => ['required', 'string'],
        ]);

        $possessionCondition = $this->possessionCondition($request, $address['possession_condition']);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:50'],
            'colour' => ['sometimes', 'required', Rule::enum(VaultColourEnum::class)],
            'icon' => ['sometimes', 'nullable', 'string', Rule::in([...Section::ICONS, ''])],
        ]);

        new UpdatePossessionConditionAction(
            user: $this->user($request),
            possessionCondition: $possessionCondition,
            name: $validated['name'] ?? $possessionCondition->name,
            colour: VaultColourEnum::from($validated['colour'] ?? $possessionCondition->colour),
            icon: array_key_exists('icon', $validated) ? $validated['icon'] : $possessionCondition->icon,
        )->execute();

        return Response::json(new PossessionConditionResource($possessionCondition)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_condition' => $schema->string()->description('The identifier of the possession condition, as list-possession-conditions gives it.')->required(),
            'name' => $schema->string()->description('The new name, up to 50 characters.'),
            'colour' => $schema->string()->enum(VaultColourEnum::values())->description('One of the vault colours, as a hexadecimal value.'),
            'icon' => $schema->string()->description('One of the glyphs of the catalogue, or an empty string to remove the icon.'),
        ];
    }
}
