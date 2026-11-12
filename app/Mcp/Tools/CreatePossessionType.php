<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossessionType as CreatePossessionTypeAction;
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

#[Description('Add a possession type to the account: a kind of thing it owns, such as office equipment or a vehicle. Add its fields afterwards with create-possession-field. The change is written to the account journal.')]
class CreatePossessionType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTypes.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'colour' => ['nullable', Rule::enum(VaultColourEnum::class)],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
        ]);

        $possessionType = new CreatePossessionTypeAction(
            user: $this->user($request),
            name: $validated['name'],
            colour: isset($validated['colour']) ? VaultColourEnum::from($validated['colour']) : null,
            icon: $validated['icon'] ?? null,
        )->execute();

        return Response::json(new PossessionTypeResource($possessionType)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('What the kind of thing is called.')->required(),
            'colour' => $schema->string()->enum(VaultColourEnum::values())->description('One of the vault colours, as a hexadecimal value. Blue when left out.'),
            'icon' => $schema->string()->enum(Section::ICONS)->description('One of the glyphs of the catalogue. None when left out.'),
        ];
    }
}
