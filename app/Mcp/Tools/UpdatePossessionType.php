<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionType as UpdatePossessionTypeAction;
use App\Enums\VaultColourEnum;
use App\Http\Resources\PossessionTypeResource;
use App\Models\Section;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a possession type, give it another vault colour, or set, change or remove its icon. What you leave out keeps its value; an empty icon removes it. The change is written to the account journal.')]
class UpdatePossessionType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTypes.update';

    public function handle(Request $request): Response
    {
        $address = $request->validate([
            'possession_type' => ['required', 'string'],
        ]);

        $possessionType = $this->possessionType($request, $address['possession_type']);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'colour' => ['sometimes', 'required', Rule::enum(VaultColourEnum::class)],
            'icon' => ['sometimes', 'nullable', 'string', Rule::in([...Section::ICONS, ''])],
        ]);

        new UpdatePossessionTypeAction(
            user: $this->user($request),
            possessionType: $possessionType,
            name: $validated['name'] ?? $possessionType->name,
            colour: VaultColourEnum::from($validated['colour'] ?? $possessionType->colour),
            icon: array_key_exists('icon', $validated) ? $validated['icon'] : $possessionType->icon,
        )->execute();

        return Response::json(new PossessionTypeResource($possessionType)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The identifier of the possession type, as list-possession-types gives it.')->required(),
            'name' => $schema->string()->description('The new name.'),
            'colour' => $schema->string()->enum(VaultColourEnum::values())->description('One of the vault colours, as a hexadecimal value.'),
            'icon' => $schema->string()->description('One of the glyphs of the catalogue, or an empty string to remove the icon.'),
        ];
    }
}
