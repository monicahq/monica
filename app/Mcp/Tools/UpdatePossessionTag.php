<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\VaultColourEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a possession tag or give it another vault colour. The change is written to the account log.')]
class UpdatePossessionTag extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTags.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_tag' => ['required', 'string'],
        ]);

        $possessionTag = $this->possessionTag($request, $validated['possession_tag']);

        return $this->answerThroughApi($request, $possessionTag->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_tag' => $schema->string()->description('The id of the possession tag, as list-possession-tags gives it.')->required(),
            'name' => $schema->string()->description('The new name, up to 50 characters.'),
            'colour' => $schema->string()->enum(VaultColourEnum::values())->description('One of the vault colours, as a hexadecimal value.'),
        ];
    }
}
