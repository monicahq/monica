<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a possession to another location of the account on a given day. The stay where it is now closes that day, and a new stay at the new location opens the same day. Refused for the location it is already in, and for a day before its current stay began. The change is written to the account log.')]
class MovePossession extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.locationStays.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $possession = $this->possession($vault, $validated['possession']);

        return $this->answerThroughApi($request, $vault->uuid, $possession->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'possession' => $schema->string()->description('The id of the possession, as list-possessions gives it.')->required(),
            'location_id' => $schema->string()->description('The id of the location it moves to, as list-locations gives it.')->required(),
            'moved_on' => $schema->string()->description('The day it moved, as YYYY-MM-DD.')->required(),
            'reason' => $schema->string()->description('Why it moved, up to 255 characters.'),
            'note' => $schema->string()->description('Anything worth knowing about the new stay, up to 5,000 characters.'),
        ];
    }
}
