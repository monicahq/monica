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

#[Description('Rename a possession type, give it another vault colour, or set, change or remove its icon. The change is written to the account log.')]
class UpdatePossessionType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTypes.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
        ]);

        $possessionType = $this->possessionType($request, $validated['possession_type']);

        return $this->answerThroughApi($request, $possessionType->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The id of the possession type, as list-possession-types gives it.')->required(),
            'name' => $schema->string()->description('The new name.'),
            'colour' => $schema->string()->enum(VaultColourEnum::values())->description('One of the vault colours, as a hexadecimal value.'),
            'icon' => $schema->string()->enum(Section::ICONS)->nullable()->description('One of the glyphs of the catalogue.'),
        ];
    }
}
