<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\VaultColourEnum;
use App\Models\Section;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a possession condition, give it another vault colour, or set, change or remove its icon. It keeps its place in the order. The change is written to the account log.')]
class UpdatePossessionCondition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionConditions.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_condition' => ['required', 'string'],
        ]);

        $possessionCondition = $this->possessionCondition($request, $validated['possession_condition']);

        return $this->answerThroughApi($request, $possessionCondition->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_condition' => $schema->string()->description('The id of the possession condition, as list-possession-conditions gives it.')->required(),
            'name' => $schema->string()->description('The new name, up to 50 characters.'),
            'colour' => $schema->string()->enum(VaultColourEnum::values())->description('One of the vault colours, as a hexadecimal value.'),
            'icon' => $schema->string()->enum(Section::ICONS)->nullable()->description('One of the glyphs of the catalogue.'),
        ];
    }
}
