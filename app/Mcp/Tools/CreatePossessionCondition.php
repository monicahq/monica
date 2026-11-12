<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossessionCondition as CreatePossessionConditionAction;
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

#[Description('Add a possession condition to the account: a state its possessions can be in, such as “mint” or “needs repair”. It goes last in the order, and its name is unique in the account ignoring case. The change is written to the account journal.')]
class CreatePossessionCondition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionConditions.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'colour' => ['nullable', Rule::enum(VaultColourEnum::class)],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
        ]);

        $possessionCondition = new CreatePossessionConditionAction(
            user: $this->user($request),
            name: $validated['name'],
            colour: isset($validated['colour']) ? VaultColourEnum::from($validated['colour']) : VaultColourEnum::Blue,
            icon: $validated['icon'] ?? null,
        )->execute();

        return Response::json(new PossessionConditionResource($possessionCondition)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('What the condition reads, up to 50 characters.')->required(),
            'colour' => $schema->string()->enum(VaultColourEnum::values())->description('One of the vault colours, as a hexadecimal value. Blue when left out.'),
            'icon' => $schema->string()->enum(Section::ICONS)->description('One of the glyphs of the catalogue. None when left out.'),
        ];
    }
}
